<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'newsystem_stockdetail';
    protected $primaryKey = 'newSystemStyleID';
    public $timestamps = false;
}
