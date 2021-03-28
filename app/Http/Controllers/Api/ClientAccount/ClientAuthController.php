<?php

namespace App\Http\Controllers\Api\ClientAccount;

use Illuminate\Http\Request;
use App\Http\models\Clients;
use App\Http\models\ClientTokens;
use Illuminate\Support\Facades\Hash;
use App\Library\FormatData;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;


class ClientAuthController extends Controller
{
    /**
     * Login
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        //Выполняю проверки и подготавливаю данные
        if(($phone_prepared =  $this->processPhone($request->input('phone'))) === false) return ['status' => 'phone_incorrect'];
        $device_token = $request->input('device_token');
        //Проверяю наличие учётных данных в базе
        if($client = $this->checkClientLogin($phone_prepared, $request->input('password'))){
            if(!$token = ClientTokens::where([['client_id', '=', $client->id], ['device_token', '=', $device_token]])->first()) {
                $token = new ClientTokens();
                $token->client_id = $client->id;
                $token->device_token = $device_token;
                $token->ip = substr($request->ip(), 0, 15);
                $token->browser = substr($request->header('User-Agent'), 0, 50);
            }
            $new_token = Str::random(60);
            $token->token = $new_token;
            $token->save();
            return ['token' => $new_token, 'status' => 'success'];
        }

        //Такие учётные данные не найдены
        return ['status' => 'client_not_found'];
    }
    /**
     * Get client
     *
     * @return \Illuminate\Http\Response
     */
    public function getClient(Request $request)
    {
        $user = Auth::user();
        return ['data' => $user, 'status' => 'success'];
    }

    //Регистрация клиента в системе
    public function register(Request $request)
    {
        if(($phone_prepared =  $this->processPhone($request->input('phone'))) === false) return ['status' => 'phone_incorrect'];
        if(!$client = $this->findClientByPhone($phone_prepared)) return ['status' => 'client_not_found'];
        if(!$password_prepared = $this->processPassword($request->input('password'))) return ['status' => 'password_incorrect'];
        if($client->registered === 1) return ['status' => 'client_already_registered'];
        $client->registered = 1;
        $client->password = Hash::make($password_prepared);
        try {
            $client->save();
        }

        catch(\Exception $err) {
            return ['status' => 'error'];
        }

        return ['status' => 'success'];
    }

    //Удаление записи с токеном клиента
    public function logout(Request $request)
    {
        return ClientTokens::where('token', $request->bearerToken())->delete();
    }

    //Проверяет телефон на соответствие шаблону
    private function checkPhone($phone)
    {
        if(strlen($phone) < 1) return false;
        if(!preg_match(FormatData::$phone_pattern, $phone)) return false;
        return true;
    }

    private function findClientByPhone($phone)
    {
        return Clients::where('phone', $phone)->first();
    }

    //Проверяте телефон, и при успехе возвращает его в формате хранения в БД
    private function processPhone($phone)
    {
        //Проверяю телефон
        if(!$this->checkPhone($phone)) return false;

        //Теперь форматирую поле под то, как телефон хранится в базе
        return FormatData::formatPhoneForDb($phone);
    }

    private function processPassword($password)
    {
        if(strlen($password) < 8) return false;
        return $password;
    }

    private function checkClientLogin($phone, $password)
    {
        if(!$client = $this->findClientByPhone($phone)) return false;
        if($client->registered !== 1) return false;
        if(!Hash::check($password, $client->password)) return false;
        return $client;
    }
}
