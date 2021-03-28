<?php
namespace App\Http\models;

use Illuminate\Database\Eloquent\Model;

class ClientTokens extends Model
{
    protected $table = 'client_tokens';
    protected $fillable = ['*'];
}