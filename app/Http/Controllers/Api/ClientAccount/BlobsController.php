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

class BlobsController extends Controller
{
    public function show($id) 
    {   
        $blob_eloq = Blobs::where('id', $id);
        if (!$blob_eloq->count()) return ['status' => 'error_blob_not_found'];
        if (Clients::where('passport_blob_id'))
        return $blob_eloq->get();

        $client_id = Auth::user()->id;
        $clients_drivers = ClientsDrivers::where('client_id', $client_id)->get();
        $ids = [];
        foreach($clients_drivers as $client_driver){
            $ids[] = $client_driver->driver_id;
        }
        return ['status' => 'success', 'data' => Clients::whereIn('id', $ids)->get()];
    }
}
