<?php

namespace App\Http\Controllers\Api\ClientAccount;

use Illuminate\Http\Request;
use App\Http\models\Clients;
use App\Http\models\Drivers;
use App\Http\models\ClientsDrivers;
use App\Http\models\Blobs;
use App\Library\FormatData;
use App\Library\ErrorHandle;
use App\Http\Controllers\Controller;
use App\Http\models\ClientsDuplicates;
use Illuminate\Support\Facades\Auth;

class DriversController extends Controller
{
    public function create(Request $request)
    {
        try {
            //Формирую массив из входных параметров
            $input = $request->input('driver');
            //Проверка существования телефона
            if(Drivers::where('phone', FormatData::formatPhoneForDb($input['phone']))->count()) return ['status' => 'error_data_phone_already_exists'];
            //Создаю клиента
            $client = new Drivers();
            //Проверка данных
            foreach(['first_name', 'middle_name', 'last_name', 'phone', 'email'] as $field){
                if(!$input[$field]) return ['status' => 'error_data_' . $field];
            }
            //Эти поля не требуют преобразования, поэтому я их складываю в базу, как есть. Чтобы это стало возможным на фронте называю поля, как в колонки в базе.
            foreach(['first_name', 'middle_name', 'last_name', 'phone', 'email'] as $field){
                if($input[$field] == "") return ['status' => 'error_data_' . $field . '_is_empty'];
                $client->$field = $input[$field];    
            }
            //Телефон я преобразую под формат дб
            $client->phone = FormatData::formatPhoneForDb($input['phone']);
            //Скан паспорта и водительского удостоверения записываю в отдельную модель
            foreach(['passport', 'driver_license'] as $blob_name){
                if($input[$blob_name. '_blob'] == "") return ['status' => 'error_data_' . $blob_name . '_blob_is_empty'];;
                if(is_int($blob_id_or_error = $this->addBlob($input[$blob_name. '_blob'], 2500000, $blob_name))) {
                    $client->{$blob_name . '_blob_id'} = $blob_id_or_error;
                } else {
                    return $blob_id_or_error;
                }
            }
            $client->save();
            //Добавляю информацию о принадлежности водителя клиенту
            $client_driver = new ClientsDrivers();
            $client_driver->client_id = $input['client_id'];
            $client_driver->driver_id = $client->id;
            $client_driver->save();
            return ['status' => 'success'];
        } catch (\Exception $e) {
            return ErrorHandle::errorForJson($e);
        }
    }

    public function show($id)
    {
        return ['status' => 'success', 'data' => Drivers::where('id', $id)->first()];
    }

    public function delete(Request $request)
    {
        try {
            $driver_id = $request->input('driver_id');
            if (!$driver = Drivers::where('id', $driver_id)->count()) return ['status' => 'error_driver_not_found'];
            $client_driver = ClientsDrivers::where([['client_id', '=', Auth::user()->id], ['driver_id', '=', $driver_id]]);
            if (!$client_driver->count()) return ['status' => 'error_bind_not_found'];
            $client_driver->delete();
            return ['status' => 'success'];
        } catch (\Exception $e) {
            return ErrorHandle::errorForJson($e);
        }
    }

    public function index(Request $request) 
    {
        $client_id = Auth::user()->id;
        $clients_drivers = ClientsDrivers::where('client_id', $client_id)->get();
        $ids = [];
        foreach($clients_drivers as $client_driver){
            $ids[] = $client_driver->driver_id;
        }
        return ['status' => 'success', 'data' => Clients::whereIn('id', $ids)->get()];
    }

    private function addBlob($blob_data, $max_size, $blob_name)
    {
        if(!$blob_data) return false;
        if(strlen($blob_data > $max_size)) return ['status' => 'error_' . $blob_name . '_file_exceeded_' . $max_size];
        $blob = new Blobs();
        $blob->data = $blob_data;
        $blob->save();
        return $blob->id;
    }

    public function update(Request $request, $id)
    {
        try {
            //Формирую массив из входных параметров
            $input = $request->input('driver');
            //Проверка существования такого же телефона, но у другого водителя
            if (Drivers::where([
                ['id', '!=', $id],
                ['phone', '=', FormatData::formatPhoneForDb($input['phone'])]
            ])->count()) return ['status' => 'error_data_other_driver_has_same_phone'];
            $driver_eloq = Drivers::where('id', $id);
            if(!$driver_eloq->count()) return ['status' => 'error_driver_not_found'];
            $driver = $driver_eloq->first();
            //Проверка данных
            foreach(['first_name', 'middle_name', 'last_name', 'phone', 'email'] as $field){
                if(!$input[$field]) return ['status' => 'error_data_' . $field];
            }
            //Эти поля не требуют преобразования, поэтому я их складываю в базу, как есть. Чтобы это стало возможным на фронте называю поля, как в колонки в базе.
            foreach(['first_name', 'middle_name', 'last_name', 'phone', 'email'] as $field){
                if($input[$field] == "") return ['status' => 'error_data_' . $field . '_is_empty'];
                $driver->$field = $input[$field];    
            }
            //Телефон я преобразую под формат дб
            $driver->phone = FormatData::formatPhoneForDb($input['phone']);
            //Скан паспорта и водительского удостоверения записываю в отдельную модель
            foreach(['passport', 'driver_license'] as $blob_name){
                if(!isset($input[$blob_name. '_blob'])) continue;
                if(is_int($blob_id_or_error = $this->addBlob($input[$blob_name. '_blob'], 2500000, $blob_name))) {
                    $driver->{$blob_name . '_blob_id'} = $blob_id_or_error;
                } else {
                    return $blob_id_or_error;
                }
            }
            $driver->save();
            return ['status' => 'success'];
        } catch (\Exception $e) {
            return ErrorHandle::errorForJson($e);
        }
    }
}
