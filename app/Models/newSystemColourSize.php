<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class newSystemColourSize extends Model
{
    use HasFactory;
    protected $table = 'newsystem_stock_colour_size';
    protected $primaryKey = 'newSystemColourSizeID';
    public $timestamps = false;
}
