<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\VaccineRegistration;
use App\Models\AdminInfo;
use App\Models\StaffManagement;

class VaccineRegController extends Controller
{
    public function regDraft(Request $request){
        $staff = StaffManagement::select('id')
                    ->where('email', $request->email)
                    ->get();
        $staff_id = $staff[0]->id; //to retrieve staff id

        $admininfoadd = [
            'vac_name' =>  $request->vaccine_name,
            'targetted_disease' =>  $request->target_disease,
            'targetted_species' =>  $request->target_species,
        ];

        $admin_info = AdminInfo::create( $admininfoadd); //to create admin info

        $admin_info_id = $admin_info->id; //to retrieve id of admin info after creation

        $vaccineregadd = [
            'staff_id' => $staff_id,
            'admin_info_id' => $admin_info_id,
            'status' => '0' // 0 indicates drafted
        ];

        $vaccine_reg = VaccineRegistration::create($vaccineregadd); //to register to master table

        return response()->json(["message" => "Berjaya disimpan sebagai draf", "code" => 200]);

    }

    public function regSave(Request $request){

        $staff = StaffManagement::select('id')
                    ->where('email', $request->email)
                    ->get();
        $staff_id = $staff[0]->id; //to retrieve staff id

        $admininfoadd = [
            'vac_name' =>  $request->vaccine_name,
            'targetted_disease' =>  $request->target_disease,
            'targetted_species' =>  $request->target_species,
        ];

        $admin_info = AdminInfo::create( $admininfoadd); //to create admin info

        $admin_info_id = $admin_info->id; //to retrieve id of admin info after creation

        $vaccineregadd = [
            'staff_id' => $staff_id,
            'admin_info_id' => $admin_info_id,
            'status' => '1' // 1 indicates submitted
        ];

        $vaccine_reg = VaccineRegistration::create($vaccineregadd); //to register to master table

        return response()->json(["message" => "Berjaya dihantar", "code" => 200]);

    }

    public function applicationList(Request $request){
        $staff = StaffManagement::select('id')
                    ->where('email', $request->email)
                    ->get();
        $staff_id = $staff[0]->id; //to retrieve staff id

        $list = VaccineRegistration::select('*', 'vaccine_reg.status as status', 'vaccine_reg.id as id') //eventhough all is selected, status & id needs to be defined otherwise it displays other variables with the same name from other tables
                                    ->join('staff_management', 'staff_management.id', '=', 'vaccine_reg.staff_id')
                                    ->join('admin_info', 'admin_info.id', '=', 'vaccine_reg.admin_info_id')
                                    ->where('vaccine_reg.staff_id', $staff_id)
                                    ->get()->toArray();

                                    return response()->json(["message" => "Application List", "list" => $list,  "code" => 200]);
    }

    public function getVacInfoList(Request $request){

        $vaccine_reg = VaccineRegistration::select('*')
                                         ->where('id',$request->id)
                                         ->get();

        $list = AdminInfo::select('*')
                        ->where('id', $vaccine_reg[0]->admin_info_id)
                        ->get();

                                    return response()->json(["message" => "Application List", "list" => $list,  "code" => 200]);
    }

    public function draftRegDraft(Request $request){

        $vaccine_reg = VaccineRegistration::select('*')
        ->where('id',$request->id)
        ->get();

        $admin_info_id = $vaccine_reg[0]->admin_info_id;

        $admininfoadd = [
            'vac_name' =>  $request->vaccine_name,
            'targetted_disease' =>  $request->target_disease,
            'targetted_species' =>  $request->target_species,
        ];

        $admin_info = AdminInfo::where('id', $admin_info_id)->update( $admininfoadd); //to create admin info


        $vaccineregadd = [
            'admin_info_id' => $admin_info_id,
            'status' => '0' // 0 indicates drafted
        ];

        $vaccine_reg = VaccineRegistration::where('id', $request->id)->update($vaccineregadd); //to register to master table

        return response()->json(["message" => "Berjaya disimpan sebagai draf", "code" => 200]);

    }

    public function draftRegSave(Request $request){

        $vaccine_reg = VaccineRegistration::select('*')
        ->where('id',$request->id)
        ->get();

        $admin_info_id = $vaccine_reg[0]->admin_info_id;

        $admininfoadd = [
            'vac_name' =>  $request->vaccine_name,
            'targetted_disease' =>  $request->target_disease,
            'targetted_species' =>  $request->target_species,
        ];

        $admin_info = AdminInfo::where('id', $admin_info_id)->update( $admininfoadd); //to create admin info

        $vaccineregadd = [
            'admin_info_id' => $admin_info_id,
            'status' => '1' // 1 indicates submitted
        ];

        $vaccine_reg = VaccineRegistration::where('id', $request->id)->update($vaccineregadd); //to register to master table

        return response()->json(["message" => "Berjaya dihantar", "code" => 200]);

    }




    public function ReturnReg(Request $request){

        $vaccineregadd = [
            'status' => '4' // 4 indicates Return
        ];

        $vaccine_reg = VaccineRegistration::where('id', $request->id)->update($vaccineregadd);

        return response()->json(["message" => "Berjaya Dikembalikan", "code" => 200]);
    }


    public function KeSaringanReg(Request $request){

        $vaccineregadd = [
            'status' => '2' // 0 indicates Next phase (Penilaian)
        ];

        $vaccine_reg = VaccineRegistration::where('id', $request->id)->update($vaccineregadd);


        return response()->json(["message" => "Berjaya  Ke Penilaian", "code" => 200]);
    }

    public function ResultPass(Request $request){

        $vaccineregadd = [
            'status' => '3', //3 indicates Pass
            'comment' => $request->comment,        ];

        $vaccine_reg = VaccineRegistration::where('id', $request->id)->update($vaccineregadd);


        return response()->json(["message" => "Status Permohonan adalah lulus", "code" => 200]);
    }

    public function ResultDecline(Request $request){

        $vaccineregadd = [
            'status' => '5', // 5 indicates Decline
            'comment' => $request->comment,
        ];

        $vaccine_reg = VaccineRegistration::where('id', $request->id)->update($vaccineregadd);


        return response()->json(["message" => "Status Permohonan adalah ditolak", "code" => 200]);
    }
}
