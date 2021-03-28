<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Http\models\Clients;
use App\Http\models\ClientsDuplicates;
use App\Http\models\Rents as Rents;
use Mockery\Undefined;

class UpdateRents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rents:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update clients rents in db';

    //Счётчики
    protected $counters = [
        'same_rent_guid' => 0,  //рента с таким гуид уже есть в бд
        'added_to_db' => 0, //успешно записано в бд
        'error_write_to_db' => 0,   //ошибок записи в бд
        'fetched_from_1c' => 0  //рент получено итого от 1с
    ];

    //Объект для взаимодействия с 1С через SOAP-драйвер
    protected $soap_object = null;

    //Объект вывода информации в консоль (объект PPrinter)
    protected $pp = null;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->soap_object = new Soap1C();
        $this->pp = new PPrinter();
        date_default_timezone_set('Europe/Moscow');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */

    public function handle()
    {
        //Очищаю список рент
        Rents::truncate();
        
        //Загрузка списка клиентов, по которым будет выполнен сбор рент
        $clients = Clients::all();

        //Вывод начальных сообщений в консоль
        $this->pp->empty();
        $this->pp->printMain();
        $this->pp->empty();
        $this->pp->out("Загрузка рент из 1С, расчёт бонусного статуса и миль");

        $start_time = time();

        //Загрузка рент по клиентам
        $this->processClients($clients, 1);

        $duplicates = ClientsDuplicates::all();

        //Заменяю гуид дубликата на гуид клиента, к которому принадлежит дубликат
        foreach($duplicates as &$duplicate){
            if($client = Clients::where('id', $duplicate->client_id)->first()){
                $duplicate->duplicate_id = $duplicate->id;
                $duplicate->id = $client->id;
                $duplicate->duplicate_guid = $duplicate->guid;
                $duplicate->guid = $client->guid;
            }
        }

        //Загрузка рент по дубликатам
        $this->processClients($duplicates, 2);

        //Вывод статистики
        $this->pp->empty();
        $this->pp->out("Времени затрачено: " . gmdate('H:i:s', time() - $start_time));
        $this->pp->empty();
        $this->pp->smallHeader("Статистика загрузки рент");
        $this->pp->out($this->counters['fetched_from_1c']. " рент загружено из 1С");
        $this->pp->out($this->counters['same_rent_guid'] . " уже есть в БД с таким же guid");
        $this->pp->out($this->counters['added_to_db'] . " успешно добавлено в БД");
        $this->pp->out($this->counters['error_write_to_db'] . " ошибок записи в БД");
        $this->pp->bye();
    }

    //Сохраняю ренту в БД, предварительно проверив существование ренты по guid
    private function saveRent($client, $rent, $client_is_duplicate)
    {
        //Проверка существования записи
        if(Rents::where('guid', '=', $rent->DocGUID)->count()){
            $this->counters['same_rent_guid']++;
            return;
        }

        //Запись модели
        try{        
            $rent_model = new Rents();
            $rent_model->guid = $rent->DocGUID;
            $rent_model->client_id = $client->id;
            $rent_model->client_guid = $client->guid;
            $rent->Car ? $rent_model->car_guid = $rent->Car[0]->GUID : null;
            if($client_is_duplicate){
                $rent_model->client_duplicate_id = $client->duplicate_id;
                $rent_model->client_duplicate_guid = $client->duplicate_guid;
            }
            $rent_model->date_begin = $rent->BeginDate;
            $rent_model->date_end = $rent->EndDate;
            $rent_model->sum = $rent->Sum;
            $rent_model->rental_days = $rent->RentalDays;
            $rent_model->mileage = $rent->Mileage;
            $rent_model->mileage_difference = $rent->MileageDifference;
            $rent_model->save();

            //Прибавляю счётчики, если сохранение выполнилось успешно
            $this->counters['added_to_db']++;
        }

        //Обработка ошибок записи модели
        catch(\Exception $e){
            $this->pp->out("Ошибка сохранения ренты в БД.");
            $this->pp->out("Данные клиента:");
            $this->pp->out($rent);
            $this->pp->empty();
            $this->pp->out("Сообщение об ошибке:");
            $this->pp->out($e->getMessage());

            //Добавляю счётчик
            $this->counters['error_write_to_db']++;
            return;
        }
    }

    //Получает список рент от 1С по протоколу SOAP
    protected function getRents($client_guid, $client_city_department)
    {
        $soap_query = '<ns0:GetCarRentByClient><ns0:ClientGUID>' .$client_guid . '</ns0:ClientGUID></ns0:GetCarRentByClient>';
        return $this->soap_object->getItems($soap_query, $this->soap_object->getLink($client_city_department), 'GetCarRentByClientResponse')->data;
    }

    //Обработка клиентов
    protected function processClients($clients, $mode = 1)
    {
        //Проверка записей в базе
        if($clients->isEmpty()){
            $this->pp->out("Отсутствуют " . ($mode === 1 ? "клиенты" : "дубликаты") . " в базе данных.");
            return;
        }

        //Начальный вывод в консоль
        $this->pp->empty();
        $this->pp->time("Старт: ");
        $this->pp->empty();
        $this->pp->out("Загрузка рент по " . ($mode === 1 ? "клиентам" : "дубликатам") . " - в прогресс-баре количество " . ($mode === 1 ? "клиентов" : "дубликатов"));

        //Подготовка прогресс бара
        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();

        //Выполняю проход по клиентам из основной модели 
        foreach($clients as $client){
            //Выполняю запрос к 1С, используя протокол SOAP
            $rents = $this->getRents($client->guid, $client->city_department);
            
            //Случай отсутствия рент по заданному клиенту
            if(count($rents) === 0) continue;

            //Счётчик "получено рент итого от 1С"
            $this->counters['fetched_from_1c'] += count($rents);

            //Пора записать собранные ренты
            foreach($rents as $rent){
                //Запись модели
                $this->saveRent($client, $rent, $mode-1);
            }

            //Сдвигаю прогресс-бар
            $bar->advance();
        }

        $bar->finish();
        $this->pp->empty();
        $this->pp->empty();
        $this->pp->time("Финиш: ");
    }
}
