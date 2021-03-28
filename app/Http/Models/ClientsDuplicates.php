<?php
namespace App\Http\models;

use Illuminate\Database\Eloquent\Model;

class ClientsDuplicates extends Model
{
    protected $table = 'clients_duplicates';
    protected $fillable = ['*'];
}