<?php
namespace App\Http\models;

use Illuminate\Database\Eloquent\Model;
use App\Models\ClientsDrivers;

class Drivers extends Model
{
    protected $table = 'clients';
    protected $fillable = ['*'];
}