<?php

namespace App\Http\Controllers\Api\ClientAccount;

use Illuminate\Http\Request;
use App\Http\models\Rents;
use App\Http\models\Cars;
use App\Http\Controllers\Controller;

class RentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //Исключаю "вредоносные" данные от пользователя
        $client_id = intval($request->input('client_id'));

        $rents = Rents::where('client_id', $client_id)->get();

        foreach($rents as &$rent){
          if($rent["car_guid"]){
            $cars = Cars::where('1cID', $rent["car_guid"])->get();
            $cars ? $rent["car"] = $cars[0] : $rent["car"] = false;
          }
        }
        
        return ['data' => $rents, 'status' => 'success'];
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
    public function show(Request $request)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
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
