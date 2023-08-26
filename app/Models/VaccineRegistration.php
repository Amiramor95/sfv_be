<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VaccineRegistration extends Model
{
    protected $table = 'vaccine_reg';
    protected $fillable = ["staff_id", "admin_info_id", "starting_material_id","vac_prod_ev_id", "finished_product_id", "comment", "status", "created_at", "updated_at"];
}
