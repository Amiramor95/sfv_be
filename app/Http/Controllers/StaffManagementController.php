<?php

namespace App\Http\Controllers;

use App\Mail\StaffReceiveMail;
use App\Mail\UpdateEmail;
use Illuminate\Http\Request;
use App\Models\StaffManagement;
use App\Models\Mentari_Staff_Transfer;
use Exception;
use Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Roles;
use App\Models\Designation;
use App\Models\GeneralSetting;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\DefaultRoleAccess;
use App\Models\ScreenAccessRoles;
use App\Models\HospitalBranchManagement;

class StaffManagementController extends Controller
{
    public function getStaffManagementListOrByEmail(Request $request){
        $list=[];

        $role= Roles::select('id')->where('role_name','Admin Pentadbir')->first();
        $list=StaffManagement::select('staff_management.name','staff_management.id','roles.role_name','staff_management.email','staff_management.status','users.id_user')
        ->join('roles', 'staff_management.role_id', '=', 'roles.id')
        ->join('users', 'users.email', '=', 'staff_management.email')
        ->where('staff_management.role_id',$role['id'])->get()->toArray();
  
        return response()->json(["message" => "Senarai Pentadbir", 'list' => $list, "code" => 200]);

    }

    public function getUserListOrByEmail(Request $request){

        $list=[];

        $role= Roles::select('id')->where('role_name','Admin Pentadbir')->first();

        $list=StaffManagement::select('staff_management.name','staff_management.id','roles.role_name','staff_management.email','staff_management.status','users.id_user')
        ->leftjoin('roles', 'roles.id','staff_management.role_id')
        ->leftjoin('users', 'users.email', '=', 'staff_management.email')
        ->where('staff_management.role_id','!=',$role['id'])->get()->toArray();
        return response()->json(["message" => "Senarai Pengguna", 'list' => $list, "code" => 200]);
    
    }

    public function UserDetail(Request $request){
    
        $details=StaffManagement::select('*')
        ->where('id',$request->id)
        ->get()->ToArray();

        return response()->json(["message" => "Senarai Pengguna", 'list' => $details, "code" => 200]);
    }

    
    public function UserRemove(Request $request){

        $email=StaffManagement::select('email')->where('id', $request->id)->first();
        $remove=User::where('email', $email['email'])->delete();
        $remove2=StaffManagement::where('id', $request->id)->delete();
// nanti tambah query delete untuk form register vaksin

        return response()->json(["message" => "Pengguna Telah Berjaya Dipadamkan",  "code" => 200]);
    }

    public function UserAdd(Request $request){
        $randomNumber=0;
        $count=0;
        $count2=1;
        while($count<$count2){
        $randomNumber = random_int(100000, 999999);
        $check= User::where('id_user',$randomNumber)->first();
        if(!$check){
            $count++;
        }
    }

            $staffadd = [
                'added_by' =>  $request->added_by,
                'name' =>  $request->name,
                'nric_no' =>  $request->nric_no,
                'address_1' => $request->address_1,
                'address_2' => $request->address_2,
                'address_3' => $request->address_3,
                'state' => $request->state,
                'city'=> $request->city,
                'postcode' => $request->postcode,
                'email' =>  $request->email,
                'role_id' => $request->role_id,
                'contact_no' =>  $request->contact_no,
                'owner_type' => $request->owner_type,
                'name_vacs_manufacturer' => $request->name_vacs_manufacturer,
                'address_vacs_factory' => $request->address_vacs_factory,
                'status' => "1"
            ];

        try {
            $check = StaffManagement::where('email', $request->email)->count();

            if ($check == 0) {
                $staff = StaffManagement::create($staffadd);
                $role = Roles::select('role_name')->where('id', $request->role_id)->first();
 
                    $default_pass =  SystemSetting::select('variable_value')
                    ->where('variable_name', "=", 'set-the-default-password-value')
                    ->first();

                    $user = User::create(
                        ['name' => $request->name, 'email' => $request->email, 'role' =>$role->role_name, 'password' => bcrypt($default_pass->variable_value),'id_user' => $randomNumber]
                    );

                    $defaultAcc = DB::table('default_role_access')
                        ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
                        ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
                        ->where('default_role_access.role_id', $request->role_id)
                        ->get();

                    if ($defaultAcc) {
                        foreach ($defaultAcc as $key) {
                            $screen = [
                                'module_id' => $key->module_id,
                                'sub_module_id' => $key->sub_module_id,
                                'screen_id' => $key->screen_id,
                                'staff_id' => $user->id,
                                'access_screen' => '1',
                                'read_writes' => '1',
                                'read_only' => '0',
                            ];

                            if (ScreenAccessRoles::where($screen)->count() == 0) {
                                $screen['added_by'] = $request->added_by;
                                ScreenAccessRoles::Create($screen);
                            }
                        }
                    }
                    $toEmail    =   $request->email;
                    $data       =   ['name' => $request->name, 'user_id' => $toEmail, 'password' => $default_pass->variable_value];
                    try {
                        Mail::to($toEmail)->send(new StaffReceiveMail($data));
                        return response()->json(["message" => "Record Created Successfully!", "code" => 200]);
                    } catch (Exception $e) {
                        return response()->json(["message" => $e->getMessage(), "code" => 500]);
                    }
                }

                return response()->json(["message" => "Record Created Successfully!", "code" => 200]);
            }
         catch (Exception $e) {
            return response()->json(["message" => $e->getMessage(), 'Staff' => $staffadd, "code" => 200]);
        }
        return response()->json(["message" => "Staff already exists!", "code" => 200]);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'added_by' => 'required|integer',
            'name' => 'required|string',
            'nric_no' => 'required|string|',
            'registration_no' => '',
            'role_id' => 'required|integer',
            'email' => 'required|string|unique:staff_management',
            'team_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'contact_no' => 'required|string',
            'designation_id' => 'required|integer',
            'is_incharge' => '',
            'designation_period_start_date' => 'required',
            'start_date' => 'required',
            'document' => '',

        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        if (($request->document == "null") || empty($request->document)) {

            $staffadd = [
                'added_by' =>  $request->added_by,
                'name' =>  $request->name,
                'nric_no' =>  $request->nric_no,
                'registration_no' =>  $request->registration_no,
                'role_id' =>  $request->role_id,
                'email' =>  $request->email,
                'team_id' =>  $request->team_id,
                'branch_id' =>  $request->branch_id,
                'contact_no' =>  $request->contact_no,
                'designation_id' =>  $request->designation_id,
                'is_incharge' =>  $request->is_incharge,
                'designation_period_start_date' =>  $request->designation_period_start_date,
                'designation_period_end_date' =>  $request->designation_period_end_date,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $request->document,
                'status' => "1"
            ];
        } else {

            $files = $request->file('document');
            $isUploaded = upload_file($files, 'StaffManagement');
            $staffadd = [
                'added_by' =>  $request->added_by,
                'name' =>  $request->name,
                'nric_no' =>  $request->nric_no,
                'registration_no' =>  $request->registration_no,
                'role_id' =>  $request->role_id,
                'email' =>  $request->email,
                'team_id' =>  $request->team_id,
                'branch_id' =>  $request->branch_id,
                'contact_no' =>  $request->contact_no,
                'designation_id' =>  $request->designation_id,
                'is_incharge' =>  $request->is_incharge,
                'designation_period_start_date' =>  $request->designation_period_start_date,
                'designation_period_end_date' =>  $request->designation_period_end_date,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $isUploaded->getData()->path,
                'status' => "1"
            ];
        }
        try {
            $check = StaffManagement::where('email', $request->email)->count();

            if ($check == 0) {
                $staff = StaffManagement::create($staffadd);
                $role = Roles::select('role_name')->where('id', $request->role_id)->first();

                $default_pass =  SystemSetting::select('status')
                ->where('variable_name', "=", 'system-generate')
                ->first();
                if ($default_pass->status == '1') {

                    $user = User::create(
                        ['name' => $request->name, 'email' => $request->email, 'role' => $role->role_name, 'password' => bcrypt('password@123')]
                    );

                    $defaultAcc = DB::table('default_role_access')
                        ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
                        ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
                        ->where('default_role_access.role_id', $request->role_id)
                        ->get();

                    $hospital = HospitalBranchManagement::where('id', $request->branch_id)->first();


                    if ($defaultAcc) {
                        foreach ($defaultAcc as $key) {
                            $screen = [
                                'module_id' => $key->module_id,
                                'sub_module_id' => $key->sub_module_id,
                                'screen_id' => $key->screen_id,
                                'hospital_id' => $hospital->hospital_id,
                                'branch_id' => $request->branch_id,
                                'team_id' => $request->team_id,
                                'staff_id' => $user->id,
                                'access_screen' => '1',
                                'read_writes' => '1',
                                'read_only' => '0',

                            ];

                            if (ScreenAccessRoles::where($screen)->count() == 0) {
                                $screen['added_by'] = $request->added_by;
                                ScreenAccessRoles::Create($screen);
                            }
                        }
                    }

                    $toEmail    =   $request->email;
                    $data       =   ['name' => $request->name, 'user_id' => $toEmail, 'password' => 'password@123'];

                    try {
                        Mail::to($toEmail)->send(new StaffReceiveMail($data));
                        return response()->json(["message" => "Record Created Successfully", "code" => 200]);
                    } catch (Exception $e) {
                        return response()->json(["message" => $e->getMessage(), "code" => 500]);
                    }
                } else if ($default_pass->status == '0') {
                    $default_pass2 =  SystemSetting::select('variable_value')
                    ->where('variable_name', "=", 'set-the-default-password-value')
                    ->first();

                    $user = User::create(
                        ['name' => $request->name, 'email' => $request->email, 'role' =>$role->role_name, 'password' => bcrypt($default_pass2->variable_value)]
                    );

                    $defaultAcc = DB::table('default_role_access')
                        ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
                        ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
                        ->where('default_role_access.role_id', $request->role_id)
                        ->get();

                    $hospital = HospitalBranchManagement::where('id', $request->branch_id)->first();


                    if ($defaultAcc) {
                        foreach ($defaultAcc as $key) {
                            $screen = [
                                'module_id' => $key->module_id,
                                'sub_module_id' => $key->sub_module_id,
                                'screen_id' => $key->screen_id,
                                'hospital_id' => $hospital->hospital_id,
                                'branch_id' => $request->branch_id,
                                'team_id' => $request->team_id,
                                'staff_id' => $user->id,
                                'access_screen' => '1',
                                'read_writes' => '1',
                                'read_only' => '0',

                            ];

                            if (ScreenAccessRoles::where($screen)->count() == 0) {
                                $screen['added_by'] = $request->added_by;
                                ScreenAccessRoles::Create($screen);
                            }
                        }
                    }


                    $toEmail    =   $request->email;
                    $data       =   ['name' => $request->name, 'user_id' => $toEmail, 'password' => $default_pass2->variable_value];
                    try {
                        Mail::to($toEmail)->send(new StaffReceiveMail($data));
                        return response()->json(["message" => "Record Created Successfully!", "code" => 200]);
                    } catch (Exception $e) {
                        return response()->json(["message" => $e->getMessage(), "code" => 500]);
                    }
                }

                return response()->json(["message" => "Record Created Successfully!", "code" => 200]);
            }
        } catch (Exception $e) {
            return response()->json(["message" => $e->getMessage(), 'Staff' => $staffadd, "code" => 200]);
        }
        return response()->json(["message" => "Staff already exists!", "code" => 200]);
    }

    public function checknricno(Request $request)
    {
        $check = StaffManagement::where('nric_no', $request->nric_no)->count();
        if ($check == 0) {
            return response()->json(["message" => "Staff Management List", 'list' => "Not Exist", "code" => 400]);
        } else {
            return response()->json(["message" => "Staff Management List", 'list' => "Exist", "code" => 200]);
        }
    }

    public function getStaffManagementList()
    {
        $users = DB::table('staff_management')
            ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
            ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
            ->select('staff_management.id', 'staff_management.name', 'general_setting.section_value as designation_name', 'hospital_branch__details.hospital_branch_name')
            ->where('staff_management.status', '=', '1')
            ->get();
        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }

    public function getStaffDetailByBranch(Request $request)
    {
        $users = DB::table('staff_management')
            ->select('id', 'name')
            ->where('status', '=', '1')
            ->where('branch_id', '=', $request->branch_id)
            ->get();
        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }

    public function getStaffDetailByRole(Request $request)
    {
        $users = DB::table('staff_management')
            ->select('id', 'name', 'role_id')
            ->where('status', '=', '1')
            ->where('branch_id', '=', $request->branch_id)
            ->where('role_name', '=', 'Medical Officer')
            ->orWhere('role_name', '=', 'Psychiatrist')
            ->get();
        return response()->json(["message" => "Staff Role List", 'list' => $users, "code" => 200]);
    }

    public function getStaffDetailById(Request $request)
    {
        $users = DB::table('staff_management')
            ->leftjoin('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
            ->join('users', 'users.email', '=', 'staff_management.email')
            ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
            ->select('staff_management.id', 'users.id as users_id', 'staff_management.name', 'general_setting.section_value as designation_name', 'hospital_branch__details.hospital_branch_name')
            ->where('staff_management.id', '=', $request->id)
            ->get();
        return response()->json(["message" => "Staff Detail", 'list' => $users, "code" => 200]);
    }

    public function getStaffManagementListOrById(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }
        if ($request->name == '' && $request->branch_id == '0') {
            $users = DB::table('staff_management')
                ->leftjoin('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->leftjoin('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('users', 'users.email', '=', 'staff_management.email')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->join('roles', 'staff_management.role_id', '=', 'roles.id')
                ->select(
                    'roles.role_name',
                    'staff_management.id',
                    'staff_management.branch_id',
                    'users.id as users_id',
                    'service_register.service_name',
                    'staff_management.name',
                    'general_setting.section_value as designation_name',
                    'hospital_branch__details.hospital_branch_name'
                )
                ->get();

            return response()->json(["message" => "Staff Management List1", 'list' => $users, "code" => 200]);
        } else if ($request->name == '' && $request->branch_id != '0') {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('users', 'staff_management.email', '=', 'users.email')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->join('roles', 'staff_management.role_id', '=', 'roles.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->select(
                    'roles.role_name',
                    'staff_management.id',
                    'users.id as users_id',
                    'staff_management.name',
                    'general_setting.section_value as designation_name',
                    'hospital_branch__details.hospital_branch_name',
                    'service_register.service_name',
                    'staff_management.status',
                    'staff_management.team_id'
                )
                ->where('staff_management.branch_id', '=', $request->branch_id)
                ->get();
            return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
        } else if ($request->branch_id == '0') {

            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->leftjoin('users', 'staff_management.email', '=', 'users.email')
                ->leftjoin('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->join('roles', 'staff_management.role_id', '=', 'roles.id')
                ->select(
                    'roles.role_name',
                    'staff_management.id',
                    'users.id as users_id',
                    'staff_management.name',
                    'general_setting.section_value as designation_name',
                    'hospital_branch__details.hospital_branch_name',
                    'service_register.service_name',
                    'staff_management.status',
                    'staff_management.team_id'
                )
                ->where('staff_management.name', 'LIKE', "%{$request->name}%")
                ->orderBy('staff_management.name', 'asc')
                ->get();
            return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
        } else {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('users', 'staff_management.email', '=', 'users.email')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->select(
                    'staff_management.id',
                    'users.id as users_id',
                    'staff_management.name',
                    'general_setting.section_value as designation_name',
                    'hospital_branch__details.hospital_branch_name',
                    'service_register.service_name',
                    'staff_management.status',
                    'staff_management.team_id'
                )
                ->where('staff_management.branch_id', '=', $request->branch_id)
                ->where('staff_management.name', 'LIKE', "%{$request->name}%")
                ->orderBy('staff_management.name', 'asc')
                ->get();
            return response()->json(["message" => "Staff Management List else", 'list' => $users, "code" => 200]);
        }
    }

    public function getUserlist(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }
        if ($request->branch_id == '0') {

            $users = DB::table('users')
                ->leftjoin('staff_management', 'users.email', '=', 'staff_management.email')
                ->select('staff_management.id', 'users.id as users_id', 'users.name', 'users.role')
                ->get();
        } else {
            $users = DB::table('users')
                ->leftjoin('staff_management', 'users.email', '=', 'staff_management.email')
                ->select('staff_management.id', 'users.id as users_id', 'users.name', 'users.role')
                ->where('staff_management.branch_id', '=', $request->branch_id)
                ->get();
        }
        return response()->json(["message" => "Users List", 'list' => $users, "code" => 200]);
    }

    public function getUserlistbyTeam(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }
            $users = DB::table('users')
                ->leftjoin('staff_management', 'users.email', '=', 'staff_management.email')
                ->select('staff_management.id', 'users.id as users_id', 'users.name', 'users.role')
                ->where('staff_management.branch_id', '=', $request->branch_id)
                ->where('staff_management.team_id', '=', $request->team_id)
                ->get();
        return response()->json(["message" => "Users List", 'list' => $users, "code" => 200]);
    }

    public function getStaffManagementListById(Request $request)
    {
        $users = DB::table('staff_management')
            ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
            ->join('hospital_branch_team_details', 'staff_management.team_id', '=', 'hospital_branch_team_details.id')
            ->select('staff_management.id', 'staff_management.name', 'general_setting.section_value as designation_name', 'hospital_branch_team_details.hospital_branch_name')
            ->where('staff_management.id', '=', $request->name)
            ->orWhere('staff_management.branch_id', '=', $request->branch_id)
            ->get();
        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }

    public function getStaffManagementListByBranchId(Request $request)
    {
        $users = DB::table('staff_management')
            ->select('staff_management.id', 'staff_management.name', 'contact_no', 'email')
            ->Where('staff_management.branch_id', '=', $request->branch_id)
            ->Where('staff_management.status', '=', '1')
            ->get();

        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }

    public function getPsychiatristByBranchId(Request $request)
    {

        $users = DB::table('staff_management')
        ->join('general_setting', 'general_setting.id', '=', 'staff_management.designation_id')
        ->select('staff_management.id', 'staff_management.name', 'staff_management.designation_id')
        ->Where('staff_management.branch_id', '=', $request->branch_id)

        ->Where('general_setting.section_value', 'like', '%Psychiatrist%')
        ->Where('staff_management.status', '=', '1')
        ->get();

            // dd($users);
        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }

    public function getStaffManagementDetailsById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        $role = StaffManagement::where('staff_management.id', $request->id)->first();

        if ($role->role_id != 0) {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('roles', 'staff_management.role_id', '=', 'roles.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select('staff_management.id as Staff_managementId', 'staff_management.name', 'staff_management.nric_no', 'general_setting.section_value as designation_name', 'staff_management.designation_period_start_date', 'staff_management.designation_period_end_date', 'staff_management.registration_no', 'roles.role_name', 'service_register.service_name', 'staff_management.branch_id', 'staff_management.is_incharge', 'staff_management.contact_no', 'staff_management.email', 'staff_management.status', 'staff_management.start_date', 'staff_management.end_date', 'hospital_branch__details.hospital_branch_name')
                ->where('staff_management.id', '=', $request->id)
                ->get();
        } else if ($role->role_id == 0) {

            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select(
                    'staff_management.id as Staff_managementId',
                    'staff_management.name',
                    'staff_management.nric_no',
                    'general_setting.section_value as designation_name',
                    'staff_management.designation_period_start_date',
                    'staff_management.designation_period_end_date',
                    'staff_management.registration_no',
                    'service_register.service_name',
                    'staff_management.branch_id',
                    'staff_management.is_incharge',
                    'staff_management.contact_no',
                    'staff_management.email',
                    'staff_management.status',
                    'staff_management.start_date',
                    'staff_management.end_date',
                    'hospital_branch__details.hospital_branch_name'
                )
                ->where('staff_management.id', '=', $request->id)
                ->get();
        }
        return response()->json(["message" => "Staff Management Details", 'list' => $users, "code" => 200]);
    }

    public function editStaffManagementDetailsById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        $role = StaffManagement::where('id', $request->id)->first();

        if ($role->role_id != 0) {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('roles', 'staff_management.role_id', '=', 'roles.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select(
                    'staff_management.id as Staff_managementId',
                    'staff_management.name',
                    'staff_management.role_id',
                    'staff_management.team_id',
                    'staff_management.nric_no',
                    'staff_management.branch_id',
                    'general_setting.section_value as designation_name',
                    'general_setting.id as designation_id',
                    'staff_management.designation_period_start_date',
                    'staff_management.designation_period_end_date',
                    'staff_management.registration_no',
                    'roles.code',
                    'roles.role_name',
                    'service_register.service_name',
                    'hospital_branch__details.hospital_branch_name',
                    'staff_management.branch_id',
                    'staff_management.is_incharge',
                    'staff_management.contact_no',
                    'staff_management.email',
                    'staff_management.status',
                    'staff_management.start_date',
                    'staff_management.end_date'
                )
                ->where('staff_management.id', '=', $request->id)
                ->get();
        } else if ($role->role_id == 0) {
            $users = DB::table('staff_management')
                ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
                ->join('service_register', 'staff_management.team_id', '=', 'service_register.id')
                ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
                ->select(
                    'staff_management.id as Staff_managementId',
                    'staff_management.name',
                    'staff_management.role_id',
                    'staff_management.team_id',
                    'staff_management.nric_no',
                    'staff_management.branch_id',
                    'general_setting.section_value as designation_name',
                    'general_setting.id as designation_id',
                    'staff_management.designation_period_start_date',
                    'staff_management.designation_period_end_date',
                    'staff_management.registration_no',
                    'service_register.service_name',
                    'hospital_branch__details.hospital_branch_name',
                    'staff_management.branch_id',
                    'staff_management.is_incharge',
                    'staff_management.contact_no',
                    'staff_management.email',
                    'staff_management.status',
                    'staff_management.start_date',
                    'staff_management.end_date'
                )
                ->where('staff_management.id', '=', $request->id)
                ->get();
        }
        return response()->json(["message" => "Staff Management Details", 'list' => $users, "code" => 200]);
    }

    public function updateStaffManagement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'added_by' => 'required|integer',
            'name' => 'required|string',
            'id' => 'required|integer',
            'nric_no' => 'required|string',
            'registration_no' => 'required|string',
            'role_id' => 'required|integer',
            'email' => 'required|string',
            'team_id' => 'required|integer',
            'branch_id' => 'required|integer',
            'contact_no' => 'required|string',
            'designation_id' => 'required|integer',
            'is_incharge' => '',
            'designation_period_start_date' => 'required',
            'designation_period_end_date' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'account_status' => 'required',
            'document' => 'mimes:png,jpg,jpeg,pdf|max:10240'
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->document == '') {

            $staffEmail = StaffManagement::where(
                ['id' => $request->id]
            )
                ->select('email')
                ->first();
            StaffManagement::where(
                ['id' => $request->id]
            )->update([
                'added_by' =>  $request->added_by,
                'name' =>  $request->name,
                'nric_no' =>  $request->nric_no,
                'registration_no' =>  $request->registration_no,
                'role_id' =>  $request->role_id,
                'email' =>  $request->email,
                'team_id' =>  $request->team_id,
                'branch_id' =>  $request->branch_id,
                'contact_no' =>  $request->contact_no,
                'designation_id' =>  $request->designation_id,
                'is_incharge' =>  $request->is_incharge,
                'designation_period_start_date' =>  $request->designation_period_start_date,
                'designation_period_end_date' =>  $request->designation_period_end_date,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $request->document,
                'status' => $request->account_status
            ]);

            $updateDetails = [
                'email' => $request->email,
                'name' => $request->name,
            ];
            DB::table('users')
                ->where('email', $staffEmail->email)
                ->update($updateDetails);

            if ($staffEmail->email != $request->email) {
                $toEmail    =   $request->email;
                $data       =   ['name' => $request->name];
                try {
                    Mail::to($toEmail)->send(new UpdateEmail($data));
                } catch (Exception $e) {
                    return response()->json(["message" => $e->getMessage(), "code" => 500]);
                }
            }

            $userId = DB::table('users')
                ->select('id')
                ->where('email', $request->email)->first();

            ScreenAccessRoles::where('staff_id', $userId->id)->delete();

            $defaultAcc = DB::table('default_role_access')
                ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
                ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
                ->where('default_role_access.role_id', $request->role_id)
                ->get();

            $hospital = HospitalBranchManagement::where('id', $request->branch_id)->first();

            if ($defaultAcc) {
                foreach ($defaultAcc as $key) {
                    $screen = [
                        'module_id' => $key->module_id,
                        'sub_module_id' => $key->sub_module_id,
                        'screen_id' => $key->screen_id,
                        'hospital_id' => $hospital->hospital_id,
                        'branch_id' => $request->branch_id,
                        'team_id' => $request->team_id,
                        'staff_id' => $userId->id,
                        'access_screen' => '1',
                        'read_writes' => '1',
                        'read_only' => '0',
                        'added_by' => $request->added_by,

                    ];
                    if (ScreenAccessRoles::where($screen)->count() == 0) {
                        ScreenAccessRoles::Create($screen);
                    }
                }
            }

            return response()->json(["message" => "Staff Management has updated successfully", "code" => 200]);
        } else {
            $files = $request->file('document');
            $isUploaded = upload_file($files, 'StaffManagement');

            $staffEmail = StaffManagement::where(
                ['id' => $request->id]
            )
                ->select('email')
                ->first();


            StaffManagement::where(
                ['id' => $request->id]
            )->update([
                'added_by' =>  $request->added_by,
                'name' =>  $request->name,
                'nric_no' =>  $request->nric_no,
                'registration_no' =>  $request->registration_no,
                'role_id' =>  $request->role_id,
                'email' =>  $request->email,
                'team_id' =>  $request->team_id,
                'branch_id' =>  $request->branch_id,
                'contact_no' =>  $request->contact_no,
                'designation_id' =>  $request->designation_id,
                'is_incharge' =>  $request->is_incharge,
                'designation_period_start_date' =>  $request->designation_period_start_date,
                'designation_period_end_date' =>  $request->designation_period_end_date,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>   $isUploaded->getData()->path,
                'status' => $request->account_status
            ]);


            DB::table('users')
                ->where('email', $staffEmail->email)
                ->update(['email' => $request->email]);

            if ($staffEmail->email != $request->email) {
                $toEmail    =   $request->email;
                $data       =   ['name' => $request->name];
                try {
                    Mail::to($toEmail)->send(new UpdateEmail($data));
                } catch (Exception $e) {
                    return response()->json(["message" => $e->getMessage(), "code" => 500]);
                }
            }

            $userId = DB::table('users')
                ->select('id')
                ->where('email', $request->email)->first();

            ScreenAccessRoles::where('staff_id', $userId->id)->delete();

            $hospital = HospitalBranchManagement::where('id', $request->branch_id)->first();

            $defaultAcc = DB::table('default_role_access')
                ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
                ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
                ->where('default_role_access.role_id', $request->role_id)
                ->get();

            if ($defaultAcc) {
                foreach ($defaultAcc as $key) {
                    $screen = [
                        'module_id' => $key->module_id,
                        'sub_module_id' => $key->sub_module_id,
                        'screen_id' => $key->screen_id,
                        'hospital_id' => $hospital->hospital_id,
                        'branch_id' => $request->branch_id,
                        'team_id' => $request->team_id,
                        'staff_id' => $userId->id,
                        'access_screen' => '1',
                        'read_writes' => '1',
                        'read_only' => '0',
                        'added_by' => $request->added_by,

                    ];
                    if (ScreenAccessRoles::where($screen)->count() == 0) {
                        ScreenAccessRoles::Create($screen);
                    }
                }
            }



            return response()->json(["message" => "Staff Management has updated successfully", "code" => 200]);
        }
    }

    public function remove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'added_by' => 'required|integer',
            'id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        StaffManagement::where(
            ['id' => $request->id]
        )->update([
            'status' => '0',
            'added_by' => $request->added_by
        ]);

        return response()->json(["message" => "Staff Removed From System!", "code" => 200]);
    }

    public function transferstaff(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'added_by' => 'required|integer',
            'old_branch_id' => 'required|integer',
            'new_branch_id' => 'required|integer',
            'staff_id' => 'required|integer',
            'start_date' => 'required',
            'end_date' => '',
            'document' => ''

        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }

        if (($request->document == "null") || empty($request->document)) {
            $staffadd = [
                'added_by' =>  $request->added_by,
                'old_branch_id' =>  $request->old_branch_id,
                'new_branch_id' =>  $request->new_branch_id,
                'staff_id' =>  $request->staff_id,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $request->document,
                'status' => "1"
            ];
        } else {
            $files = $request->file('document');
            $isUploaded = upload_file($files, 'TransferStaff');
            $staffadd = [
                'added_by' =>  $request->added_by,
                'old_branch_id' =>  $request->old_branch_id,
                'new_branch_id' =>  $request->new_branch_id,
                'staff_id' =>  $request->staff_id,
                'start_date' =>  $request->start_date,
                'end_date' =>  $request->end_date,
                'document' =>  $isUploaded->getData()->path,
                'status' => "1"
            ];
        }

        try {
            StaffManagement::where(
                ['id' => $request->staff_id]
            )->update([
                'added_by' =>  $request->added_by,
                'branch_id' =>  $request->new_branch_id
            ]);
            $HOD = Mentari_Staff_Transfer::firstOrCreate($staffadd);
        } catch (Exception $e) {
            return response()->json(["message" => $e->getMessage(), 'Staff' => $staffadd, "code" => 200]);
        }
        return response()->json(["message" => "Transfer Successfully!", "code" => 200]);
    }

    public function getAdminList()
    {
        $users = DB::table('staff_management')
            ->select(
                'roles.role_name',
                'staff_management.id',
                'staff_management.name',
                'general_setting.section_value as designation_name',
                'hospital_branch__details.hospital_branch_name',
                'users.id as staffId'
            )
            ->join('general_setting', 'staff_management.designation_id', '=', 'general_setting.id')
            ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
            ->join('roles', 'staff_management.role_id', '=', 'roles.id')
            ->join('users', 'staff_management.email', '=', 'users.email')
            ->where('staff_management.status', '=', '1')
            ->where('roles.code', '!=', 'null')
            ->orderBy('staff_management.name', 'asc')
            ->get();
        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }

    public function setSystemAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "code" => 422]);
        }



        $user = DB::table('staff_management')
            ->select('staff_management.email', 'staff_management.branch_id', 'staff_management.team_id', 'hospital_branch__details.hospital_id')
            ->join('hospital_branch__details', 'staff_management.branch_id', '=', 'hospital_branch__details.id')
            ->where('staff_management.id', $request->id)->first();

        $userId = DB::table('users')
            ->select('id')
            ->where('email', $user->email)->first();

        $role = roles::where('code', 'superadmin')->first();
        StaffManagement::where('id', $request->id)->update(['role_id' => $role->id]);

        $defaultAcc = DB::table('default_role_access')
            ->select('default_role_access.id as role_id', 'screens.id as screen_id', 'screens.sub_module_id as sub_module_id', 'screens.module_id as module_id')
            ->join('screens', 'screens.id', '=', 'default_role_access.screen_id')
            ->where('default_role_access.role_id', $role->id)
            ->get();


        if ($defaultAcc) {
            foreach ($defaultAcc as $key) {
                $screen = [
                    'module_id' => $key->module_id,
                    'sub_module_id' => $key->sub_module_id,
                    'screen_id' => $key->screen_id,
                    'hospital_id' => $user->hospital_id,
                    'branch_id' => $user->branch_id,
                    'team_id' => $user->team_id,
                    'staff_id' => $userId->id,
                    'access_screen' => '1',
                    'read_writes' => '1',
                    'read_only' => '0',
                    'added_by' => $request->added_by,

                ];

                if (ScreenAccessRoles::where($screen)->count() == 0) {
                    ScreenAccessRoles::Create($screen);
                }
            }
            return response()->json(["message" => "Role Assigned Successfully", "code" => 200]);
        }
    }

    public function removeUserAccess(Request $request)
    {
        $user = DB::table('staff_management')
            ->select('email')
            ->where('id', $request->id)->first();

        $userId = DB::table('users')
            ->select('id')
            ->where('email', $user->email)->first();

        ScreenAccessRoles::where('staff_id', $userId->id)->delete();
        StaffManagement::where('id', $request->id)->update(['role_id' => '']);

        return response()->json(["message" => "User Access successfully removed", "code" => 200]);
    }

    public function getRoleCode(Request $request)
    {
        $users = DB::table('staff_management')
            ->select('roles.code')
            ->join('roles', 'staff_management.role_id', '=', 'roles.id')
            ->where('staff_management.email', '=', $request->email)
            ->first();

        return response()->json(["message" => "Staff Management Details", 'list' => $users, "code" => 200]);
    }

    public function getAllStaffManagement()
    {
        $users = DB::table('staff_management')
            ->select('id', 'name')
            ->where('status', '=', '1')
            ->get();
        return response()->json(["message" => "Staff Management List", 'list' => $users, "code" => 200]);
    }


    
}
