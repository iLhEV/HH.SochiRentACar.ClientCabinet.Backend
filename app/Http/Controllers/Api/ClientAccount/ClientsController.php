<?php

namespace App\Http\Controllers\Api\ClientAccount;

use Illuminate\Http\Request;
use App\Http\models\Clients;
use App\Library\FormatData;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ClientsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (Auth::user()->id != $id) return ['status' => 'error_access'];
        $client = Clients::where('id', $id)->first();
        return ['data' => $client, 'status' => 'success'];
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (Auth::user()->id != $id) return ['status' => 'error_access'];
        //Проверка существования клиента
        if(!$client = Clients::where('id', intval($id))->first()){
            return ['message' => 'Клиент не найден', 'status' => 'error'];
        }
        
        //Подготавливаю значения (поля имён)
        foreach(['first_name', 'middle_name', 'last_name'] as $field_name) {
            if(!preg_match(FormatData::$names_pattern, $request->input($field_name))) return ['status' => 'input_data_error_' . $field_name];
            $input[$field_name] = $request->input($field_name);
        }
        //Подготавливаю значения (числовые поля)
        foreach(['age', 'sex'] as $field_name) {
            $input[$field_name] = intval($request->input($field_name));
        }
        //Телефон
        $field_name = 'phone';
        $phone_raw_data = $request->input($field_name);
        if(!preg_match(FormatData::$phone_pattern, $phone_raw_data)) return ['status' => 'input_data_error_' . $field_name];
        $input[$field_name] = FormatData::formatPhoneForDb($phone_raw_data);

        //Запись значений
        try {
            foreach(['first_name', 'middle_name', 'last_name', 'age', 'sex', 'phone'] as $field_name) {
                $client->{$field_name} = $input[$field_name];
            }
            $client->save();
        }

        //Обработка ошибок
        catch(\Exception $err) {
            return ['status' => 'error'];
        }

        //По умолчанию статус выполнения операции - успех
        return ['status' => 'success'];
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
