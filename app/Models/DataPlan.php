<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataPlan extends Model
{
    use HasFactory;

    protected $table = 'data_plans';

    protected $fillable = [
        'balance',
        'pin',
        'operator_card_id',
    ];
}
