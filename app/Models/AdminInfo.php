<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminInfo extends Model
{
    protected $table = 'admin_info';
    protected $fillable = ["vac_name", "targetted_disease", "targetted_species","condition_disease"];
}
