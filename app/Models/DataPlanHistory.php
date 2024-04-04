<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPlanHistory extends Model
{
    use HasFactory;
    
    protected $table = 'wallets';

    protected $fillable = [
        'balance',
        'pin',
        'user_id',
        'card_number',
    ];
}
