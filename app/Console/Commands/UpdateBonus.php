<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use GuzzleHttp\Client;
use App\Http\models\Clients;
use App\Http\models\ClientsDuplicates;
use App\Http\models\Rents as Rents;
use Mockery\Undefined;

class UpdateBonus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bonus:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update bonus program values';



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
        $this->pp->printMain();
        $this->pp->out("Обновление значений для бонусной программы по клиентам");
        $this->pp->startMessage();
        $start_time = time();
        $this->updateBonusValues();
        $this->pp->stopMessage();
        $this->pp->out("Времени затрачено: " . gmdate('H:i:s', time() - $start_time));
        $this->pp->bye();
    }

    protected function updateBonusValues()
    {   
        $clients = Clients::all();

        //Подготовка прогресс бара
        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();
        
        //Собираю данные для бонусной программы по каждому клиенту
        //Отдельный сбор рент по дубликатам не требуется, т.к. при добавлении ренты в базу в ней указывается client_id основного клиента
        foreach($clients as $client){
            //Начальные значения
            $mileage_sum = 0; $days_sum = 0;

            //Заполнение
            if($rents = Rents::where('client_id', $client->id)->get()){
                foreach($rents as $rent){
                    $mileage_sum += $rent->mileage_difference;
                    $days_sum += $rent->rental_days;
                }
            }

            //Запись бонусных значений
            $this->saveBonus($client, $mileage_sum, $days_sum);

            $bar->advance();
        }

        $bar->finish();

        $this->pp->empty();
    }

    //Рассчитываю бонусный статус, основываясь на суммарном количестве миль по клиенту и дней, делаю запись в БД
    private function saveBonus($client, $mileage_sum, $days_sum)
    {
        $client->mileage = $mileage_sum;
        $client->bonus_miles = intdiv($mileage_sum, 2) + ($days_sum * 50);
        $client->rental_days = $days_sum;

        //Рассчитываю статус в бонусной программе
        if($days_sum >= 100 && $mileage_sum >= 10000) {
            $client->bonus_status = 4;
        } elseif ($days_sum >= 50 && $mileage_sum >= 5000) {
            $client->bonus_status = 3;
        } elseif ($days_sum >= 10 && $mileage_sum >= 1000) {
            $client->bonus_status = 2;
        } elseif ($days_sum >= 1 && $mileage_sum >= 100) {
            $client->bonus_status = 1;
        }

        $client->save();
    }
}
