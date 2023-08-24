<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffManagement extends Model
{
    use HasFactory;
    protected $table = 'staff_management';
    protected $fillable = ['added_by', 'name', 'nric_no', 'address_1', 'address_2', 'address_3', 'state', 'city', 'poscode', 'role_id', 'email', 'team_id', 'branch_id',
    'contact_no', 'designation_id', 'name_vacs_manufacturer', 'address_vacs_factory', 'reg_num_vacs_manufacturer', 'owner_type', 'status', 'code', 'created_at', 'updated_at'];
}
