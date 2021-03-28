<?php


namespace App\Console\Commands;


class PPrinter
{
    function printMain(){
        echo PHP_EOL;
        echo "|==================================================|\n";
        echo PHP_EOL;
    }
    function printHeader($message){
        echo "|==================================================|\n";
        echo "| {$message}\n";
        echo "|==================================================|\n";
    }

    function printMessage($message){
        echo "| {$message}\n";
    }
    function printEnd(){
        echo "|==================================================|\n";
        echo "|==================meow-meow-meow==================|\n";
        echo "|==================================================|\n";
    }

    //Выводит одну запись в консоль
    public function print($misc)
    {
        print_r($misc);
        print_r(PHP_EOL);
    }

    //Выводит шапку со временем старта.
    //Принимает фразу для вывода в качестве аргумента.
    //Возвращает метку времени старта.
    public function header($text)
    {
        $this->printMain();
        $this->out($text);
        $this->startMessage();
        return time();
    }

    //Выводит подвал со временем старта.
    //Принимает метку времени старта, как аргумент. 
    public function footer($start_time)
    {
        $this->stopMessage();
        $this->out("Времени затрачено: " . gmdate('H:i:s', time() - $start_time));
        $this->bye();
    }

    //Алиас для функции print
    public function out($misc)
    {
        $this->print($misc);
   }


    //Выводит время во зоне UTC в консоль
    public function time($prefix = "")
    {
        print_r($prefix . date("G:i:s T") . PHP_EOL);
    }

    //Выводит пустую строку в консоль
    public function empty($count = 1)
    {
        for($i=1;$i<=$count;$i++){
            print_r(PHP_EOL);
        }
    }

    //Выводит заголовок малого размера
    public function smallHeader($header)
    {
        print_r("==== $header ====" . PHP_EOL);
    }

    //Выводит завершающее сообщение
    public function bye()
    {
        print_r(PHP_EOL);
    }

    public function phrase($text)
    {
        echo $text;
    }

    //Выводит время начала выполнения с надписью
    public function startMessage()
    {
        $this->time("Старт: ");
    }

    //Выводит время окончания выполнения с надписью
    public function stopMessage()
    {
        $this->time("Финиш: ");
    }

    public function spentTimeMessage($start_time)
    {
        print_r("Времени затрачено: " . gmdate('H:i:s', time() - $start_time) . PHP_EOL); 
    }
}
