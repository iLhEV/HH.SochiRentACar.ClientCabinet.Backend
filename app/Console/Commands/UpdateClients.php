<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\models\Clients;
use App\Http\models\ClientsDuplicates;
use Carbon\Carbon;
use App\Library\FormatData;

class UpdateClients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clients:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Добление клиентов в базу данных';

    //Отвечает за вывод статистики по каждому подразделению
    protected $show_department_statistics = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */

    //Объект для взаимодействия с 1С через SOAP-драйвер
    protected $soap_object = null;

    //Объект вывода информации в консоль (объект PPrinter)
    protected $pp = null;

    //Шаблон счётчиков
    protected $counters_template = [
        'page_count' => 0,
        'fetched_from_1c' => 0,
        'null' => null,
        'same_guid' => 0,
        'duplicate_same_guid' => 0,
        'empty_names' => 0,
        'same_passport_and_names' => 0,
        'same_passport_and_phone' => 0,
        'same_phone' => 0,
        'duplicate_add_errors' => 0,
        'duplicate_add_ok' => 0,
        'write_to_db_success' => 0,
        'write_to_db_error' => 0
    ];

    //Счётчики будут заполнены значениями по-умолчанию из переменной $counters_template
    protected $department_counters = null;

    public function __construct()
    {
        parent::__construct();
        $this->soap_object = new Soap1C();
        $this->pp = new PPrinter();
        date_default_timezone_set('Europe/Moscow');
    }

    public function handle()
    {
        //Выставляю счётчики общей статистики согласно шаблона
        $general_statistics = $this->counters_template;
        $this->pp->empty();
        $this->pp->printMain();
        $this->pp->empty();
        $this->pp->out("Начинаю обновление базы клиентов");
        $this->pp->out("Количество подразделений: " . count($this->soap_object->getLinks()));
        $this->pp->out("В прогресс-барах будет отображено количество обработанных страниц");
        $this->pp->startMessage();
        $start_time = time();
        foreach($this->soap_object->getLinks() as $city_department => $link){
            //Сброс счётчиков по подразделению
            $this->department_counters = $this->counters_template;

            //Возможность пропустить часть подразделений на время разработки
            //if($city_department != 'sochi'){continue;}

            //Вывожу начальную инфу
            $this->pp->out("Подразделение: " . $city_department . ", старт " . date("G:i:s T"));

            //Получаю список всех клиентов по подразделению от 1С
            $this->processDepartment($city_department, $link);
            
            //Вывод статистики по подразделению
            if($this->show_department_statistics){
                $this->pp->out("+++++++ Статистика обработки +++++++");
                $this->showStatistics($this->department_counters);
            }
            $this->pp->empty();

            //Подготовка общей статистики
            foreach($this->department_counters as $counter_name => $counter_value){
                $general_statistics[$counter_name] += $counter_value;
            }
        }

        $this->pp->stopMessage();
        $this->pp->out("Времени затрачено: " . gmdate('H:i:s', time() - $start_time));
        $this->pp->empty();
        //Вывод общей статистики
        $this->pp->smallHeader("Теперь вывожу общую статистику сбора");
        $this->showStatistics($general_statistics);
        $this->pp->empty();
    }

    //Проверка и запись данных о клиенте в базу
    private function saveClient($client, $city_department)
    {
        //Пропускаю юр лиц (те клиенты, у которых не заполнены все три имени)
        if(trim($client->FirstName) === "" && trim($client->MiddleName) === "" && trim($client->LastName) === ""){
            $this->department_counters['empty_names']++;
            return;
        }
        //Пропускаю клиентов, которые уже есть в базе (такой же guid)
        if(Clients::where(function ($query) use ($client) {
            $query->where('guid', '=', $client->GUID);
        })->count()){
            $this->department_counters['same_guid']++;
            return;
        }
        //Пропускаю дубликаты, которые уже есть в базе (такой же guid)
        if(ClientsDuplicates::where('guid', $client->GUID)->count()){
            $this->department_counters['duplicate_same_guid']++;
            return;
        }

        //Нахожу в базе клиентов, совпадающих по одному из признаков с текущим обрабатываемым клиентом
        $client_same_by_passport = Clients::getByPassport($client->PassportSeries, $client->PassportNumber);    //Одинаковые паспортные данные
        $client_same_by_names = Clients::getByNames($client->FirstName, $client->MiddleName, $client->LastName);    //Одинаковые все три имени
        $client_same_by_phone = Clients::getByPhone(FormatData::formatPhoneForDb($client->Phone));    //Одинаковый номер телефона

        $duplicate_entry = false; $duplicate_reason = 0; 
        //Совпадение телефона
        if($client_same_by_phone){
            $this->department_counters['same_phone']++;
            $duplicate_entry =  true;
            $duplicate_reason = 1;
            $client_duplicate = $client_same_by_phone;
        }
        
        //Совпадение паспорта и всех трёх имён
        if($client_same_by_passport && $client_same_by_names){
            if(($client_same_by_names && $client_same_by_passport->id === $client_same_by_names->id)){
                $this->department_counters['same_passport_and_names']++;
                $duplicate_entry =  true;
                $duplicate_reason = 2;
                $client_duplicate = $client_same_by_passport;
            }
        }

        // if($client_same_by_phone && $client_same_by_passport->id === $client_same_by_phone->id){
        //     $this->department_counters['same_passport_and_phone']++;
        //     $duplicate_entry =  true;
        //     //Проверка случая, когда выполнены оба условия
        //     $duplicate_reason = 3;
        // }


        //Клиент является дубликатом
        if($duplicate_entry){
            //Запись данных о дубликате в базу в таблицу дубликатов
            $this->addDuplicate($client_duplicate, $client, $duplicate_reason, $city_department);

            //Заканчиваю обработку и не добавляем нового клиента
            return;
        }
        
        //Добавление нового клиента в базу
        try {
            $client_model = new Clients();
            $client_model->guid = $client->GUID;
            $client_model->first_name = $client->FirstName;
            $client_model->middle_name = $client->MiddleName;
            $client_model->last_name = $client->LastName;
            $client_model->comment  = $client->Comment;
            $client_model->email  = $client->EMail;
            $client_model->phone  = FormatData::formatPhoneForDb($client->Phone);
            $client_model->passport_series  = $client->PassportSeries;
            $client_model->passport_number  = $client->PassportNumber;
            $client_model->passport_date_of_issue  = $client->PassportDateOfIssue;
            $client_model->passport_validity  = $client->PassportValidity;
            $client_model->passport_issued_by  = $client->PassportIssuedBy;
            $client_model->passport_unit_code  = $client->PassportUnitCode;
            $client_model->driver_license_series  = $client->DriverLicenseSeries;
            $client_model->driver_license_number  = $client->DriverLicenseNumber;
            $client_model->driver_license_date_of_issues  = $client->DriverLicenseDateOfIssues;
            $client_model->driver_license_validity  = $client->DriverLicenseValidity;
            $client_model->city_department = $city_department;
            $client_model->save();
            //Прибавляю счётчик
            $this->department_counters['write_to_db_success']++;
        }

        //Обработаю ошибку, если запись не удалась
        catch(\Exception $e){
            $this->pp->out("Ошибка добавления нового клиента {$client->GUID} в БД.");
            $this->pp->out("Данные клиента:");
            $this->pp->out($client);
            $this->pp->out(PHP_EOL . "Сообщение об ошибке: ");
            $this->pp->out($e->getMessage());
            $this->department_counters['write_to_db_error']++;
        }
    }

    //Получает списка записей из 1С по всем страницам, проверка и запись в БД
    function processDepartment($city_department, $link)
    { 
        $page_count = 1;
        //Выполняю последовательно запросы для каждой страницы
        for($page = 1; $page <= $page_count; $page++) {
            $data = $this->getPage($link, $page);
            if($page === 1) {
                $page_count = $this->department_counters['page_count'] = $data->PageCount;
                //Создаю прогресс-бар здесь, потому что на этом этапе я уже знаю количество страниц
                $bar = $this->output->createProgressBar($page_count);
                $bar->start();
            }
            //Предполагаю, что первая страница не вернёт null
            //Иначе если использовать в коде $bar выше, то он 
            //ещё не будет объявление и VSCode будет материться
            //Вообще хорошо бы продумать, что делать если здесь вернётся null
            if($data === null || $data->data === null){
                $bar->advance();
                continue; 
            }
            //Теперь для каждого клиента для текущей страницы выполняю проверки и делаю записи в базу
            foreach($data->data as $client){
                //Пропускаю "пустышки", обычно такое случается, когда страница ответа заполнена не полностью
                if($client === NULL) {$this->department_counters['null']++; continue;}
                //Проверка и сохранение
                $this->saveClient($client, $city_department);
                //Счётчик
                $this->department_counters['fetched_from_1c']++;
            }
            $bar->advance();
            //break;
        }
        $bar->finish();
        //В качестве ответа возвращаю истину
        return true;
    }

    //Получает записи из 1С по одной конкретной странице
    function getPage($link, $page)
    {
        $soap_query = '<ns0:GetClients><ns0:RequestType>0</ns0:RequestType><ns0:Page>' . $page . '</ns0:Page></ns0:GetClients>';
        $data = $this->soap_object->getItems($soap_query, $link, 'GetClientsResponse');
        return $data;
    }

    //Запись данных о клиенте-дубликате в отдельную таблицу дубликатов
    function addDuplicate($parent_client, $duplicate, $duplicate_reason, $city_department)
    {
        //! Наличие в базе записи с таким же GUID проверяется в вызывающей функции

        try{
            $duplicate_model = new ClientsDuplicates();
            $duplicate_model->client_id = $parent_client->id;
            $duplicate_model->guid = $duplicate->GUID;
            $duplicate_model->duplicate_reason = $duplicate_reason;
            $duplicate_model->first_name = $duplicate->FirstName;
            $duplicate_model->middle_name = $duplicate->MiddleName;
            $duplicate_model->last_name = $duplicate->LastName;
            $duplicate_model->comment  = $duplicate->Comment;
            $duplicate_model->email  = $duplicate->EMail;
            $duplicate_model->phone  = FormatData::formatPhoneForDb($duplicate->Phone);
            $duplicate_model->passport_series  = $duplicate->PassportSeries;
            $duplicate_model->passport_number  = $duplicate->PassportNumber;
            $duplicate_model->passport_date_of_issue  = $duplicate->PassportDateOfIssue;
            $duplicate_model->passport_validity  = $duplicate->PassportValidity;
            $duplicate_model->passport_issued_by  = $duplicate->PassportIssuedBy;
            $duplicate_model->passport_unit_code  = $duplicate->PassportUnitCode;
            $duplicate_model->driver_license_series  = $duplicate->DriverLicenseSeries;
            $duplicate_model->driver_license_number  = $duplicate->DriverLicenseNumber;
            $duplicate_model->driver_license_date_of_issues  = $duplicate->DriverLicenseDateOfIssues;
            $duplicate_model->driver_license_validity  = $duplicate->DriverLicenseValidity;
            $duplicate_model->city_department = $city_department;
            $duplicate_model->save();
            //Прибавляю счётчик
            $this->department_counters['duplicate_add_ok']++;
        }

        //Обрабатываю ошибку добавления данных о дубликате в БД
        catch(\Exception $e){
            $this->pp->out("Ошибка сохранения дубликата клиента в БД.");
            $this->pp->out("Данные дубликата:");
            $this->pp->out($duplicate);
            $this->pp->out(PHP_EOL . "Текст ошибки: ");
            $this->pp->out($e->getMessage());
            $this->department_counters['duplicate_add_errors']++;
        }
    }

    protected function showStatistics($counters)
    {
        $this->pp->out('Обработано страничек: ' . $counters['page_count']);
        $this->pp->out('Получено из 1С: ' . $counters['fetched_from_1c']);
        $this->pp->out('Клиенты-пустышки = NULL (обычно такое бывает, когда страничка с SOAP-ответом не полная - последняя страничка): ' . $counters['null']);
        $this->pp->out('Такой guid уже есть в базе: ' . $counters['same_guid']);
        $this->pp->out('Дубликат с таким guid уже есть в базе: ' . $counters['duplicate_same_guid']);
        $this->pp->out('Юр лицо - все три имени пустые: ' . $counters['empty_names']);
        $this->pp->out('Одинаковые паспорт и все имена: ' . $counters['same_passport_and_names']);
        $this->pp->out('Одинаковые паспорт и телефон: ' . $counters['same_passport_and_phone']);
        $this->pp->out('Ошибок при сохранении в БД: ' . $counters['write_to_db_error']);
        $this->pp->out('Ошибок сохранения дубликата в БД: ' . $counters['duplicate_add_errors']);
        $this->pp->out('Успешно сохранено дубликатов в БД: ' . $counters['duplicate_add_ok']);
        $this->pp->out('Успешно сохранено записей в БД: ' . $counters['write_to_db_success']);
    }
}


