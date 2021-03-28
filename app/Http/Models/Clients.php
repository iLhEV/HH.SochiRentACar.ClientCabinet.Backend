<?php
namespace App\Http\models;

use Illuminate\Database\Eloquent\Model;

class Clients extends Model
{
    protected $table = 'clients';
    protected $fillable = ['*'];

    public static function findByGuid($guid)
    {
      return boolval(Clients::where('guid', $guid)->count());
    }

    public static function getByNames($first_name, $middle_name, $last_name)
    {
      return Clients::where([
        ['first_name', '=', $first_name],
        ['middle_name', '=', $middle_name],
        ['last_name', '=', $last_name]
      ])->first();
    }

    public static function getByPassport($passport_series, $passport_number)
    {
      return Clients::where([
        ['passport_series', '=', $passport_series],
        ['passport_number', '=', $passport_number],
      ])->first();
    }

    public static function getByPhone($phone)
    {
      return Clients::where([
        ['phone', '=', $phone]
      ])->first();
    }
}