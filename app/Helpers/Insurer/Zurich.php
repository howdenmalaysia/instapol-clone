<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\ExtraCover;
use App\DataTransferObjects\Motor\Response\ResponseData;
use App\DataTransferObjects\Motor\Response\PremiumResponse;
use App\Helpers\HttpClient;
use App\Interfaces\InsurerLibraryInterface;
use App\Models\APILogs;
use Carbon\Carbon;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class Zurich implements InsurerLibraryInterface
{
    private string $host;
    private string $host_vix;
    private string $transaction_ref_no;
    private string $request_datetime;//2017-03-17T23:00:00.000
    private string $participant_code;
    // private string $participant_code = "02";
    private string $agent_code;
    // private string $agent_code = "D12345-123";//"D02940-000";
    private string $password;
    // private string $password = "U2FsdGVkX18hgpr790ocAAPWOf/BrXc+b/MkPaGsGbs=";
    private string $secret_key;
    // private string $secret_key = "M9R5YK*?3uuM!9wu";
    private string $trx_ref_no = "020000008";

    private const SOAP_ACTION_DOMAIN = 'https://gtws2.zurich.com.my/ziapps/zurichinsurance/services';
    private const EXTRA_COVERAGE_LIST = ['01','02','03','06','07','101','103','108','109','111',
    '112','19','22','25','57','72','89','97','200','201','202','203'];

	public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;
        
		$this->host_vix = config('insurer.config.zurich_config.host_vix');
		$this->agent_code = config('insurer.config.zurich_config.agent_code');
		$this->secret_key = config('insurer.config.zurich_config.secret_key');
		$this->participant_code = config('insurer.config.zurich_config.participant_code');

        // $getVIXNCD = (object)[
        //     'request_datetime' => "2017-Mar-17 11:00:00 PM",
        //     'id' => "850321-07-5179",
        //     'VehNo' => "WA823H", 
        // ];
        // $vehInputMake = (object)[
        //     'request_datetime' => "2017-Mar-17 11:00:00 PM",        
        //     'product_code' => "PZ01",
        // ];
        // $vehInputModel = (object)[
        //     'request_datetime' => "2017-Mar-17 11:00:00 PM",        
        //     'product_code' => "PZ01",
        //     'make_year' => "2010",
        //     'make' => "08",
        //     'filter_key' => "CAYENNE",
        // ];
        // $cover_note = (object)[
        //     'request_datetime' => "2017-Mar-17 11:00:00 PM",        
        //     'transaction_ref_no' => "020000008",
        //     'id' => "850321-07-5179",
        //     // 'VehNo' => "WA823H",
        //     'VehNo' => "WCA1451qwe",
        //     'quotationNo' => 'MQ220000107081',
        //     'trans_type' => 'B',
        //     'pre_VehNo' => '',
        //     'product_code' => 'PZ01',
        //     'cover_type' => 'V-CO',
        //     'ci_code' => 'MX1',
        //     'eff_date' => '13/12/2022',
        //     'exp_date' => '12/12/2023',
        //     'new_owned_Veh_ind' => 'Y',
        //     'VehReg_date' => '10/03/2016',
        //     'ReconInd' => 'N',
        //     'modcarInd' => 'Y',
        //     'modperformanceaesthetic' => 'O',
        //     'modfunctional' => '2,128',
        //     'yearofmake' => '2010',
        //     'make' => '11',
        //     'model' => '11*1030',
        //     'capacity' => '1497',
        //     'uom' => 'CC',
        //     'engine_no' => 'EWE323WS',
        //     'chasis_no' => 'PM2L252S002107437',
        //     'logbook_no' => 'ERTGRET253',
        //     'reg_loc' => 'L',
        //     'region_code' => 'W',
        //     'no_of_passenger' => '5',
        //     'no_of_drivers' => '1',
        //     'ins_indicator' => 'P',
        //     'name' => 'TAN AI LING',
        //     'ins_nationality' => 'L',
        //     'new_ic' => '530102-06-5226',
        //     'other_id' => '',
        //     'date_of_birth' => '22/12/1998',
        //     'age' => '27',
        //     'gender' => 'M',
        //     'marital_sts' => 'M',
        //     'occupation' => '99',
        //     'mobile_no' => '012-3456789',
        //     'off_ph_no' => '03-45678900',
        //     'email' => 'zurich.api@gmail.com',
        //     'address' => '20, Jalan PJU, Taman A, Petaling Jaya',
        //     'postcode' => '50150',
        //     'state' => '06',
        //     'country' => 'MAS',
        //     'sum_insured' => '290000.00',
        //     'av_ind' => 'N',
        //     'vol_excess' => '01',
        //     'pac_ind' => 'N',
        //     'all_driver_ind' => 'Y',
        //     'abisi' => '28000.00',
        //     'chosen_si_type' => 'REC_SI',
        //     'nationality' => 'MAS',
        //     'ext_cov_code' => '101',
        //     'unit_day' => '0',
        //     'unit_amount' => '0',
        //     'ecd_eff_date' => '14/1/2017',
        //     'ecd_exp_date' => '13/1/2018',
        //     'ecd_sum_insured' => '0',
        //     'no_of_unit' => '1',
        //     'ecd_pac_code' => 'R0075',
        //     'ecd_pac_unit' => '1', 
        //     'nd_name' => 'TAMMY TAN',
        //     'nd_identity_no' => '981211-11-1111',
        //     'nd_date_of_birth' => '11/12/1990',
        //     'nd_gender' => 'F',
        //     'nd_marital_sts' => 'S',
        //     'nd_occupation' => '99',
        //     'nd_relationship' => '5',
        //     'nd_nationality' => 'MAS',
        //     'pac_rider_no' => 'TAMMY TAN',
        //     'pac_rider_name' => '981211-11-1111',
        //     'pac_rider_new_ic' => '11/12/1990',
        //     'pac_rider_old_ic' => 'F',
        //     'pac_rider_dob' => 'S',
        //     'default_ind' => '99',
        // ];
        // $issue_cover_note = (object)[
        //     'request_datetime' => "2017-Mar-17 11:00:00 PM",
        //     'transaction_ref_no' => '020000008',
        //     'quotationNo' => 'MQ220000107081',
        // ];
        // $resend_cover_note = (object)[
        //     'request_datetime' => "2017-Mar-17 11:00:00 PM",
        //     'transaction_ref_no' => '020000008',
        //     'VehNo' => '',
        //     'cover_note_no' => 'D02940-20000037',
        //     'email_to' => 'mrbigchiam@gmail.com',
        // ];
        // $jpj_status = (object)[
        //     'request_datetime' => "2017-03-17T23:00:00.000",
        //     'transaction_ref_no' => '020000008',
        //     'cover_note_no' => 'D02940-20000037',
        // ];
        // $result = $this->getVIXNCD($getVIXNCD);
	}

    public function vehInputMake(object $input) : object
    {
        $path = 'GetVehicleMake';
        $request_datetime = $input->request_datetime;
        $product_code = $input->product_code;
        $signature = array(
            'request_datetime' => $request_datetime,
            'product_code' => $product_code,
        );
        $sign_type = "veh_m";
        $data["data"] = "{ 'AgentCode' : '".$this->agent_code.
            "','ParticipantCode' : '".$this->participant_code.
            "','RequestDateTime' : '".$request_datetime.
            "','ProductCode' : '".$product_code.
            "','Signature' : '". $this->generateSignature($signature, $sign_type)."'}";

        // Generate XML from view
        $xml = view('backend.xml.zurich.veh_input_make')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        
        if(!$result->status) {
            return $this->abort($result->response);
        }

        $data = $result->response->GetVehicleMakeResponse->GetVehicleMakeResult;
        
        $decode = htmlspecialchars_decode($data);
        $xml_data = simplexml_load_string($decode);
        $index = 0;
        $response = [];
        foreach($xml_data as $key => $value){
            if($key == 'Make'){
                $response['Make'][$index]['sdfMakeID'] = (string)$value->sdfMakeID;
                $response['Make'][$index]['Description'] = (string)$value->Description;
                $index++;
            }
            else if($key == 'Result'){
                $response['Result']['ResponseCode'] = (string)$value->ResponseCode;
                $response['Result']['Description'] = (string)$value->Description;
            }
        }
        return new ResponseData([
            'response' => (object)$response
        ]);  
    }

    private function vehInputModel(object $input) : object
    {
        
        $path = 'GetVehicleModel';
        $request_datetime = $input->request_datetime;
        $product_code = $input->product_code;
        $make_year = $input->make_year;
        $make = $input->make;
        $filter_key = $input->filter_key;
        $signature = array(
            'request_datetime' => $request_datetime,
            'product_code' => $product_code,
        );
        $sign_type = "veh_m";
        if(isset($filter_key) && $filter_key != ''){
            $data["data"] = "{ 'AgentCode' : '".$this->agent_code.
                "','ParticipantCode' : '".$this->participant_code.
                "','RequestDateTime' : '".$request_datetime.
                "','ProductCode' : '".$product_code.
                "','MakeYear' : '".$make_year.
                "','Make' : '".$make.
                "','FilterKey' : '".$filter_key.
                "','Signature' : '". $this->generateSignature($signature, $sign_type)."'}";
        }
        else{
            $data["data"] = "{ 'AgentCode' : '".$this->agent_code.
                "','ParticipantCode' : '".$this->participant_code.
                "','RequestDateTime' : '".$request_datetime.
                "','ProductCode' : '".$product_code.
                "','MakeYear' : '".$make_year.
                "','Make' : '".$make.
                "','Signature' : '". $this->generateSignature($signature, $sign_type)."'}";
        }
        // Generate XML from view
        $xml = view('backend.xml.zurich.veh_input_model')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);

        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }     
        $data = $result->response->GetVehicleModelResponse->GetVehicleModelResult;
        $xml_data = simplexml_load_string($data);
        $index = 0;
        $response = [];
        foreach($xml_data as $key => $value){
            if($key == 'Model'){
                $response['Model'][$index]['sdfVehModelID'] = (string)$value->sdfVehModelID;
                $response['Model'][$index]['Description'] = (string)$value->Description;
                $response['Model'][$index]['BodyType'] = (string)$value->BodyType;
                $response['Model'][$index]['CapacityFrom'] = (string)$value->CapacityFrom;
                $response['Model'][$index]['CapacityTo'] = (string)$value->CapacityTo;
                $response['Model'][$index]['UOM'] = (string)$value->UOM;
                $index++;
            }      
            else if($key == 'Result'){
                $response['Result']['ResponseCode'] = (string)$value->ResponseCode;
                $response['Result']['Description'] = (string)$value->Description;
            }
        }
        return new ResponseData([
            'response' => (object)$response
        ]);
    }

    private function generateSignature($input, $sign_type = null) : string
    {
        if($sign_type == 'vehinfo'){
            $id = $input['id'];
            $VehNo = $input['VehNo'];
            $request_datetime = $input['request_datetime'];
            
            $signature = $this->agent_code . $id . $this->participant_code . $request_datetime . $this->secret_key . $VehNo;
        }
        else if($sign_type == 'veh_m'){
            $product_code = $input['product_code'];
            $request_datetime = $input['request_datetime'];
            
            $signature = $this->agent_code . $this->participant_code . $request_datetime . $this->secret_key . $product_code;
        }
        else{
            $transaction_ref_no = $input['transaction_ref_no'];
            $request_datetime = $input['request_datetime'];
        
            $signature = $this->participant_code. '*' .$this->agent_code. '*' .$transaction_ref_no. '*' .$request_datetime. '*' .$this->secret_key;
        }

        return strtoupper(hash('sha512', $signature));
    }
    
    public function vehicleDetails(object $input) : object
    {

    }

    public function quotation(object $input) : object
    {
        //participant
        $participant_code = $this->participant_code;
        $transaction_ref_no = $input->transaction_ref_no;
        $request_datetime = $input->request_datetime;
        $id = $input->id;
        $VehNo = $input->VehNo;
        $signature = array(
            'request_datetime' => $request_datetime,
            'transaction_ref_no' => $transaction_ref_no,
        );
        $hashed_signature = $this->generateSignature($signature);
        $getmail = $input->getmail;
        if(count((array)$getmail)>1){
            $CNMailId = implode(', ', (array)$getmail);
        }
        else{
            $CNMailId = (array)$getmail;
        }
        
        $data["participant_code"] = $participant_code;
        $data["transaction_reference"] = $transaction_ref_no;
        $data["request_datetime"] = $request_datetime;
        $data["hashcode"] = $hashed_signature;
        $data["cn_mail"] = $CNMailId;

        //basic details
        $quotationNo = $input->quotationNo ?? '';
        $agent_code = $this->agent_code;
        $trans_type = $input->trans_type;//'B';
        $VehNo = $input->VehNo;//'WCA1451qwe';
        $pre_VehNo = $input->pre_VehNo;//'';
        $product_code = $input->product_code;//'PZ01';
        $cover_type = $input->cover_type;//'V-CO';
        $ci_code = $input->ci_code;//'MX1';
        $eff_date = $input->eff_date;//'12/12/2022';
        $exp_date = $input->exp_date;//'11/12/2023';
        $new_owned_Veh_ind = $input->new_owned_Veh_ind;//'Y';
        $VehReg_date = $input->VehReg_date;//'10/03/2016';
        $ReconInd = $input->ReconInd;//'N';
        $modcarInd = $input->modcarInd;//'Y';
        $modperformanceaesthetic = $input->modperformanceaesthetic;//'0;2';
        $modfunctional = $input->modfunctional;//'2;128';
        $yearofmake = $input->yearofmake;//'2010';
        $make = $input->make;//'11';
        $model = $input->model;//'11*1030';
        $capacity = $input->capacity;//'1497';
        $uom = $input->uom;//'CC';
        $engine_no = $input->engine_no;//'EWE323WS';
        $chasis_no = $input->chasis_no;//'PM2L252S002107437';
        $logbook_no = $input->logbook_no;//'ERTGRET253';
        $reg_loc = $input->reg_loc;//'L';
        $region_code = $input->region_code;//'W';
        $no_of_passenger = $input->no_of_passenger;//'5';
        $no_of_drivers = $input->no_of_drivers;//'1';
        $ins_indicator = $input->ins_indicator;//'P';
        $name = $input->name;//'TAN AI LING';
        $ins_nationality = $input->ins_nationality;//'L';
        $new_ic = $input->new_ic;//'530102-06-5226';
        $other_id = $input->other_id ?? '';
        if($new_ic == ''){
            if($other_id == ''){
                return 'Others ID cannot be blank if New IC Number is blank';
            }
        }
        $date_of_birth = $input->date_of_birth;//'22/12/1998';
        $age = $input->age;//'27';
        $gender = $input->gender;//'M';
        $marital_sts = $input->marital_sts;//'M';
        $occupation = $input->occupation;//'99';
        $mobile_no = $input->mobile_no;//'012-3456789';
        $off_ph_no = $input->off_ph_no;//'03-45678900';
        $email = $input->email;//'zurich.api@gmail.com';
        $address = $input->address;//'20, Jalan PJU, Taman A, Petaling Jaya';
        $postcode = $input->postcode;//'50150';
        $state = $input->state;//'06';
        $country = $input->country;//'MAS';
        $sum_insured = $input->sum_insured;//'30000.00';
        $av_ind = $input->av_ind;//'Y';
        $vol_excess = $input->vol_excess;//'01';
        $pac_ind = $input->pac_ind;//'Y';
        $pac_type = $input->pac_type;//'TAGPLUS PAC';
        $pac_unit = $input->pac_unit;//'1';
        $all_driver_ind = $input->all_driver_ind;//'Y';
        $abisi = $input->abisi;//'28000.00';
        $chosen_si_type = $input->chosen_si_type;//'REC_SI';

        $data["quotation_no"] = $quotationNo;
        $data["agent_code"] = $agent_code;
        $data["trans_type"] = $trans_type;
        $data["veh_no"] = $VehNo;
        $data["pre_veh_no"] = $pre_VehNo;
        $data["product_code"] = $product_code;
        $data["cover_type"] = $cover_type;
        $data["CI_code"] = $ci_code;
        $data["eff_date"] = $eff_date;
        $data["exp_date"] = $exp_date;
        $data["new_owned_veh_ind"] = $new_owned_Veh_ind;
        $data["veh_reg_date"] = $VehReg_date;
        $data["recon_ind"] = $ReconInd;
        $data["mod_car_ind"] = $modcarInd;
        $data["mod_performance_aesthentic"] = $modperformanceaesthetic;
        $data["mod_functional"] = $modfunctional;
        $data["year_of_make"] = $yearofmake;
        $data["make"] = $make;
        $data["model"] = $model;
        $data["capacity"] = $capacity;
        $data["uom"] = $uom;
        $data["engine_no"] = $engine_no;
        $data["chassis_no"] = $chasis_no;
        $data["logbook_no"] = $logbook_no;
        $data["reg_loc"] = $reg_loc;
        $data["region_code"] = $region_code;
        $data["no_of_passenger"] = $no_of_passenger;
        $data["no_of_drivers"] = $no_of_drivers;
        $data["ins_indicator"] = $ins_indicator;
        $data["name"] = $name;
        $data["ins_nationality"] = $ins_nationality;
        $data["new_ic"] = $new_ic;
        $data["others_id"] = $other_id;
        $data["date_of_birth"] = $date_of_birth;
        $data["age"] = $age;
        $data["gender"] = $gender;
        $data["marital_sts"] = $marital_sts;
        $data["occupation"] = $occupation;
        $data["mobile_no"] = $mobile_no;
        $data["off_ph_no"] = $off_ph_no;
        $data["email"] = $email;
        $data["address"] = $address;
        $data["post_code"] = $postcode;
        $data["state"] = $state;
        $data["country"] = $country;
        $data["sum_insured"] = $sum_insured;
        $data["av_ind"] = $av_ind;
        $data["vol_excess"] = $vol_excess;
        $data["pac_ind"] = $pac_ind;
        $data["pac_type"] = $pac_type;
        $data["pac_unit"] = $pac_unit;
        $data["all_driver_ind"] = $all_driver_ind;
        $data["abisi"] = $abisi;
        $data["chosen_si_type"] = $chosen_si_type;

        //motor extra cover details
        $ext_cov_code = $input->ext_cov_code;//'101';
        $unit_day = $input->unit_day;//'7';
        $unit_amount = $input->unit_amount;//'50';
        $ecd_eff_date = $input->ecd_eff_date;//'14/1/2017';
        $ecd_exp_date = $input->ecd_exp_date;//'13/1/2018';
        $ecd_sum_insured = $input->ecd_sum_insured;//'3000';
        $no_of_unit = $input->no_of_unit;//'1';

        $data["ext_cov_code"] = $ext_cov_code;
        $data["unit_day"] = $unit_day;
        $data["unit_amount"] = $unit_amount;
        $data["ECD_eff_date"] = $ecd_eff_date;
        $data["ECD_exp_date"] = $ecd_exp_date;
        $data["ECD_sum_insured"] = $ecd_sum_insured;
        $data["no_of_unit"] = $no_of_unit;

        //PAC extra cover details
        $ecd_pac_code = $input->ecd_pac_code;//'R0075';
        $ecd_pac_unit = $input->ecd_pac_unit;//'1';

        $data["ecd_pac_code"] = $ecd_pac_code;
        $data["ecd_pac_unit"] = $ecd_pac_unit;
        $path = 'CalculatePremium';
        // Generate XML from view
        $xml = view('backend.xml.zurich.calculate_premium')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }     
        $result_data = $result->response->CalculatePremiumResponse->XmlResult;
        $xml_data = simplexml_load_string($result_data);
        //respone
        $index = 0;
        $response = [];
        $response['PremiumDetails']['BasicPrem'] = $xml_data->PremiumDetails->BasicPrem;
        $response['PremiumDetails']['LoadPct'] = $xml_data->PremiumDetails->LoadPct;
        $response['PremiumDetails']['LoadAmt'] = $xml_data->PremiumDetails->LoadAmt;
        $response['PremiumDetails']['TuitionLoadPct'] = $xml_data->PremiumDetails->TuitionLoadPct;
        $response['PremiumDetails']['TuitionLoadAmt'] = $xml_data->PremiumDetails->TuitionLoadAmt;
        $response['PremiumDetails']['AllRiderAmt'] = $xml_data->PremiumDetails->AllRiderAmt;
        $response['PremiumDetails']['NCDAmt'] = $xml_data->PremiumDetails->NCDAmt;
        $response['PremiumDetails']['VolExcessDiscPct'] = $xml_data->PremiumDetails->VolExcessDiscPct;
        $response['PremiumDetails']['VolExcessDiscAmt'] = $xml_data->PremiumDetails->VolExcessDiscAmt;
        $response['PremiumDetails']['TotBasicNetAmt'] = $xml_data->PremiumDetails->TotBasicNetAmt;
        $response['PremiumDetails']['TotExtCoverPrem'] = $xml_data->PremiumDetails->TotExtCoverPrem;
        $response['PremiumDetails']['RnwBonusPct'] = $xml_data->PremiumDetails->RnwBonusPct;
        $response['PremiumDetails']['RnwBonusAmt'] = $xml_data->PremiumDetails->RnwBonusAmt;
        $response['PremiumDetails']['GrossPrem'] = $xml_data->PremiumDetails->GrossPrem;
        $response['PremiumDetails']['CommPct'] = $xml_data->PremiumDetails->CommPct;
        $response['PremiumDetails']['CommAmt'] = $xml_data->PremiumDetails->CommAmt;
        $response['PremiumDetails']['RebatePct'] = $xml_data->PremiumDetails->RebatePct;
        $response['PremiumDetails']['RebateAmt'] = $xml_data->PremiumDetails->RebateAmt;
        $response['PremiumDetails']['GST_Pct'] = $xml_data->PremiumDetails->GST_Pct;
        $response['PremiumDetails']['GST_Amt'] = $xml_data->PremiumDetails->GST_Amt;
        $response['PremiumDetails']['StampDutyAmt'] = $xml_data->PremiumDetails->StampDutyAmt;
        $response['PremiumDetails']['TotMtrPrem'] = $xml_data->PremiumDetails->TotMtrPrem;
        $response['PremiumDetails']['NettPrem'] = $xml_data->PremiumDetails->NettPrem;
        $response['PremiumDetails']['BasicAnnualPrem'] = $xml_data->PremiumDetails->BasicAnnualPrem;
        $response['PremiumDetails']['ActPrem'] = $xml_data->PremiumDetails->ActPrem;
        $response['PremiumDetails']['NonActPrem'] = $xml_data->PremiumDetails->NonActPrem;
        $response['PremiumDetails']['ExcessType'] = $xml_data->PremiumDetails->ExcessType;
        $response['PremiumDetails']['ExcessAmt'] = $xml_data->PremiumDetails->ExcessAmt;
        $response['PremiumDetails']['TotExcessAmt'] = $xml_data->PremiumDetails->TotExcessAmt;
        $response['PremiumDetails']['PAC_SumInsured'] = $xml_data->PremiumDetails->PAC_SumInsured;
        $response['PremiumDetails']['PAC_Prem'] = $xml_data->PremiumDetails->PAC_Prem;
        $response['PremiumDetails']['PAC_AddPrem'] = $xml_data->PremiumDetails->PAC_AddPrem;
        $response['PremiumDetails']['PAC_GrossPrem'] = $xml_data->PremiumDetails->PAC_GrossPrem;
        $response['PremiumDetails']['PAC_GSTPct'] = $xml_data->PremiumDetails->PAC_GSTPct;
        $response['PremiumDetails']['PAC_GSTAmt'] = $xml_data->PremiumDetails->PAC_GSTAmt;
        $response['PremiumDetails']['PAC_StampDuty'] = $xml_data->PremiumDetails->PAC_StampDuty;
        $response['PremiumDetails']['PAC_CommPct'] = $xml_data->PremiumDetails->PAC_CommPct;
        $response['PremiumDetails']['PAC_CommAmt'] = $xml_data->PremiumDetails->PAC_CommAmt;
        $response['PremiumDetails']['PAC_RebatePct'] = $xml_data->PremiumDetails->PAC_RebatePct;
        $response['PremiumDetails']['PAC_RebateAmt'] = $xml_data->PremiumDetails->PAC_RebateAmt;
        $response['PremiumDetails']['PAC_TotPrem'] = $xml_data->PremiumDetails->PAC_TotPrem;
        $response['PremiumDetails']['PAC_NettPrem'] = $xml_data->PremiumDetails->PAC_NettPrem;
        $response['PremiumDetails']['TtlPayablePremium'] = $xml_data->PremiumDetails->TtlPayablePremium;
        $response['PremiumDetails']['GstOnCommAmt'] = $xml_data->PremiumDetails->GstOnCommAmt;
        $response['PremiumDetails']['PAC_GstOnCommAmt'] = $xml_data->PremiumDetails->PAC_GstOnCommAmt;
        $response['PremiumDetails']['Quote_Exp_Date'] = $xml_data->PremiumDetails->Quote_Exp_Date;
        $response['PremiumDetails']['Chosen_Vehicle_SI_Lower_Bound'] = $xml_data->PremiumDetails->Chosen_Vehicle_SI_Lower_Bound;
        $response['PremiumDetails']['Chosen_Vehicle_SI_Upper_Bound'] = $xml_data->PremiumDetails->Chosen_Vehicle_SI_Upper_Bound;
        $response['PremiumDetails']['AV_SI_Value'] = $xml_data->PremiumDetails->AV_SI_Value;
        $response['PremiumDetails']['Rec_SI_Value'] = $xml_data->PremiumDetails->Rec_SI_Value;

        foreach($xml_data->ExtraCoverData as $value){
            $response['MotorExtraCoverDetails'][$index]['ExtCoverCode'] = (string)$value->ExtCoverCode;
            $response['MotorExtraCoverDetails'][$index]['ExtCoverPrem'] = (string)$value->ExtCoverPrem;
            $response['MotorExtraCoverDetails'][$index]['ExtCoverSumInsured'] = (string)$value->ExtCoverSumInsured;
            $response['MotorExtraCoverDetails'][$index]['Compulsory_Ind'] = (string)$value->Compulsory_Ind;
            $response['MotorExtraCoverDetails'][$index]['sequence'] = '';
            $index++;
        }
        $response['ReferralDetails']['ReferralCode'] = $xml_data->ReferralData->Referral_Decline_Code;
        $response['ReferralDetails']['ReferralMessage'] = $xml_data->ReferralData->Referral_Message;
        $response['ErrorDetails']['ErrorCode'] = $xml_data->Error_Display->Error_Code;
        $response['ErrorDetails']['ErrorDesc'] = $xml_data->Error_Display->Error_Desc;
        $response['ErrorDetails']['Remarks'] = $xml_data->Error_Display->Remarks;
        $response['ErrorDetails']['WarningInd'] = $xml_data->Error_Display->Warning_Ind;
        $response['QuotationInfo']['QuotationNo'] = $xml_data->QuotationInfo->QuotationNo;
        $response['QuotationInfo']['NCDMsg'] = $xml_data->QuotationInfo->NCDMsg;
        $response['QuotationInfo']['NCDPct'] = $xml_data->QuotationInfo->NCDPct;
        
        return new ResponseData([
            'response' => (object)$response
        ]); 
    }

    public function cover_note(object $input) : object
    {
        $participant_code = $this->participant_code;
        $transaction_ref_no = $input->transaction_ref_no;
        $request_datetime = $input->request_datetime;
        $signature = array(
            'request_datetime' => $request_datetime,
            'transaction_ref_no' => $transaction_ref_no,
        );
        $hashed_signature = $this->generateSignature($signature);

        $data["participant_code"] = $participant_code;
        $data["transaction_reference"] = $transaction_ref_no;
        $data["request_datetime"] = $request_datetime;
        $data["hashcode"] = $hashed_signature;
        //basic details
        $quotationNo = $input->quotationNo ?? '';//'MQ220000038991';
        $agent_code = $this->agent_code;
        $trans_type = $input->trans_type;//'B';
        $VehNo = $input->VehNo;//'WCA1451qwe';
        $pre_VehNo = $input->pre_VehNo ?? '';//'';
        $product_code = $input->product_code;//'PZ01';
        $cover_type = $input->cover_type;//'V-CO';
        $ci_code = $input->ci_code;//'MX1';
        $eff_date = $input->eff_date;//'12/12/2022';
        $exp_date = $input->exp_date;//'11/12/2023';
        $new_owned_Veh_ind = $input->new_owned_Veh_ind;//'Y';
        $VehReg_date = $input->VehReg_date;//'10/03/2016';
        $ReconInd = $input->ReconInd;//'N';
        $modcarInd = $input->modcarInd;//'Y';
        $modperformanceaesthetic = $input->modperformanceaesthetic;//'O';
        $modfunctional = $input->modfunctional;//'2;128';
        $yearofmake = $input->yearofmake;//'2010';
        $make = $input->make;//'11';
        $model = $input->model;//'11*1030';
        $capacity = $input->capacity;//'1497';
        $uom = $input->uom;//'CC';
        $engine_no = $input->engine_no;//'EWE323WS';
        $chasis_no = $input->chasis_no;//'PM2L252S002107437';
        $logbook_no = $input->logbook_no;//'ERTGRET253';
        $reg_loc = $input->reg_loc;//'L';
        $region_code = $input->region_code;//'W';
        $no_of_passenger = $input->no_of_passenger;//'5';
        $no_of_drivers = $input->no_of_drivers;//'1';
        $ins_indicator = $input->ins_indicator;//'P';
        $name = $input->name;//'TAN AI LING';
        $ins_nationality = $input->ins_nationality;//'L';
        $new_ic = $input->new_ic;//'530102-06-5226';
        $other_id = $input->other_id;//'';
        $date_of_birth = $input->date_of_birth;//'22/12/1998';
        $age = $input->age;//'27';
        $gender = $input->gender;//'M';
        $marital_sts = $input->marital_sts;//'M';
        $occupation = $input->occupation;//'99';
        $mobile_no = $input->mobile_no;//'012-3456789';
        $off_ph_no = $input->off_ph_no;//'03-45678900';
        $email = $input->email;//'zurich.api@gmail.com';
        $address = $input->address;//'20, Jalan PJU, Taman A, Petaling Jaya';
        $postcode = $input->postcode;//'50150';
        $state = $input->state;//'06';
        $country = $input->country;//'MAS';
        $sum_insured = $input->sum_insured;//'29000.00';
        $av_ind = $input->av_ind;//'N';
        $vol_excess = $input->vol_excess;//'01';
        $pac_ind = $input->pac_ind;//'N';
        $pac_type = $input->pac_type;//'TAGPLUS PAC';
        $pac_unit = $input->pac_unit;//'1';
        $all_driver_ind = $input->all_driver_ind;//'Y';
        $abisi = $input->abisi;//'28000.00';
        $chosen_si_type = $input->chosen_si_type;//'REC_SI';

        $data["quotation_no"] = $quotationNo;
        $data["agent_code"] = $agent_code;
        $data["trans_type"] = $trans_type;
        $data["veh_no"] = $VehNo;
        $data["pre_veh_no"] = $pre_VehNo;
        $data["product_code"] = $product_code;
        $data["cover_type"] = $cover_type;
        $data["CI_code"] = $ci_code;
        $data["eff_date"] = $eff_date;
        $data["exp_date"] = $exp_date;
        $data["new_owned_veh_ind"] = $new_owned_Veh_ind;
        $data["veh_reg_date"] = $VehReg_date;
        $data["recon_ind"] = $ReconInd;
        $data["mod_car_ind"] = $modcarInd;
        $data["mod_performance_aesthentic"] = $modperformanceaesthetic;
        $data["mod_functional"] = $modfunctional;
        $data["year_of_make"] = $yearofmake;
        $data["make"] = $make;
        $data["model"] = $model;
        $data["capacity"] = $capacity;
        $data["uom"] = $uom;
        $data["engine_no"] = $engine_no;
        $data["chassis_no"] = $chasis_no;
        $data["logbook_no"] = $logbook_no;
        $data["reg_loc"] = $reg_loc;
        $data["region_code"] = $region_code;
        $data["no_of_passenger"] = $no_of_passenger;
        $data["no_of_drivers"] = $no_of_drivers;
        $data["ins_indicator"] = $ins_indicator;
        $data["name"] = $name;
        $data["ins_nationality"] = $ins_nationality;
        $data["new_ic"] = $new_ic;
        $data["others_id"] = $other_id;
        $data["date_of_birth"] = $date_of_birth;
        $data["age"] = $age;
        $data["gender"] = $gender;
        $data["marital_sts"] = $marital_sts;
        $data["occupation"] = $occupation;
        $data["mobile_no"] = $mobile_no;
        $data["off_ph_no"] = $off_ph_no;
        $data["email"] = $email;
        $data["address"] = $address;
        $data["post_code"] = $postcode;
        $data["state"] = $state;
        $data["country"] = $country;
        $data["sum_insured"] = $sum_insured;
        $data["av_ind"] = $av_ind;
        $data["vol_excess"] = $vol_excess;
        $data["pac_ind"] = $pac_ind;
        $data["pac_type"] = $pac_type;
        $data["pac_unit"] = $pac_unit;
        $data["all_driver_ind"] = $all_driver_ind;
        $data["abisi"] = $abisi;
        $data["chosen_si_type"] = $chosen_si_type;  
        //motor extra cover details
        $ext_cov_code = $input->ext_cov_code;//'101';
        $unit_day = $input->unit_day;//'0';
        $unit_amount = $input->unit_amount;//'0';
        $ecd_eff_date = $input->ecd_eff_date;//'14/1/2017';
        $ecd_exp_date = $input->ecd_exp_date;//'13/1/2018';
        $ecd_sum_insured = $input->ecd_sum_insured;//'0';
        $no_of_unit = $input->no_of_unit;//'1';

        $data["ext_cov_code"] = $ext_cov_code;
        $data["unit_day"] = $unit_day;
        $data["unit_amount"] = $unit_amount;
        $data["ECD_eff_date"] = $ecd_eff_date;
        $data["ECD_exp_date"] = $ecd_exp_date;
        $data["ECD_sum_insured"] = $ecd_sum_insured;
        $data["no_of_unit"] = $no_of_unit;
        //Motor Additional Named Driver Details        
        $nd_name = $input->nd_name;//'TAMMY TAN';
        $nd_identity_no = $input->nd_identity_no;//'981211-11-1111';
        $nd_date_of_birth = $input->nd_date_of_birth;//'11/12/1990';
        $nd_gender = $input->nd_gender;//'F';
        $nd_marital_sts = $input->nd_marital_sts;//'S';
        $nd_occupation = $input->nd_occupation;//'99';
        $nd_relationship = $input->nd_relationship;//'5';
        $data['additional_driver'] = [];
        array_push($data['additional_driver'], (object) [
            'nd_name' => $nd_name,
            'nd_identity_no' => $nd_identity_no,
            'nd_date_of_birth' => $nd_date_of_birth,
            'nd_gender' => $nd_gender,
            'nd_marital_sts' => $nd_marital_sts,
            'nd_occupation' => $nd_occupation,
            'nd_relationship' => $nd_relationship,
        ]);
        //PAC Rider Details
        $pac_rider_no = $input->pac_rider_no;//'TAMMY TAN';
        $pac_rider_name = $input->pac_rider_name;//'981211-11-1111';
        $pac_rider_new_ic = $input->pac_rider_new_ic;//'11/12/1990';
        $pac_rider_old_ic = $input->pac_rider_old_ic;//'F';
        $pac_rider_dob = $input->pac_rider_dob;//'S';
        $default_ind = $input->default_ind;//'99';
        $data['pac_rider'] = [];
        array_push($data['pac_rider'], (object) [
            'pac_rider_no' => $pac_rider_no,
            'pac_rider_name' => $pac_rider_name,
            'pac_rider_new_ic' => $pac_rider_new_ic,
            'pac_rider_old_ic' => $pac_rider_old_ic,
            'pac_rider_dob' => $pac_rider_dob,
            'default_ind' => $default_ind,
        ]);
        //PAC extra cover details
        $ecd_pac_code = $input->ecd_pac_code;//'R0075';
        $ecd_pac_unit = $input->ecd_pac_unit;//'1';//
        $data["ecd_pac_code"] = $ecd_pac_code;
        $data["ecd_pac_unit"] = $ecd_pac_unit;
        $path = 'CalculatePremium';
        // Generate XML from view
        $xml = view('backend.xml.zurich.calculate_premium')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }     
        $result_data = $result->response->CalculatePremiumResponse->XmlResult;
        $xml_data = simplexml_load_string($result_data);

        return new ResponseData([
            'response' => (object)$xml_data
        ]); 
    }

    public function issue_cover_note(object $input) : object
    {
        $participant_code = $this->participant_code;
        $transaction_ref_no = $input->transaction_ref_no;
        $request_datetime = $input->request_datetime;
        $signature = array(
            'request_datetime' => $request_datetime,
            'transaction_ref_no' => $transaction_ref_no,
        );
        $hashed_signature = $this->generateSignature($signature);
        $data["participant_code"] = $participant_code;
        $data["transaction_reference"] = $transaction_ref_no;
        $data["request_datetime"] = $request_datetime;
        $data["hashcode"] = $hashed_signature;
        //Issue Cover Note Details
        $quotationNo = $input->quotationNo;//'MQ220000038991';
        $agent_code = $this->agent_code;
        $data["quotation_no"] = $quotationNo;
        $data["agent_code"] = $agent_code;

        $path = 'IssueCoverNote';
        // Generate XML from view
        $xml = view('backend.xml.zurich.issue_cover_note')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }     
        $result_data = $result->response->IssueCoverNoteResponse->XmlResult;
        $xml_data = simplexml_load_string($result_data);
        $response['CoverNoteInfo']['CoverNoteNo'] = $xml_data->CoverNoteInfo->CoverNoteNo ?? '';
        $response['CoverNoteInfo']['NCDMsg'] = $xml_data->CoverNoteInfo->NCDMsg;
        return new ResponseData([
            'response' => (object)$response
        ]); 
    }
    
    public function resend_cover_note(object $input) : object
    {
        $participant_code = $this->participant_code;
        $transaction_ref_no = $input->transaction_ref_no;
        $request_datetime = $input->request_datetime;
        $signature = array(
            'request_datetime' => $request_datetime,
            'transaction_ref_no' => $transaction_ref_no,
        );
        $hashed_signature = $this->generateSignature($signature);
        $data["participant_code"] = $participant_code;
        $data["transaction_reference"] = $transaction_ref_no;
        $data["request_datetime"] = $request_datetime;
        $data["hashcode"] = $hashed_signature;

        $agent_code = $this->agent_code;
        $VehNo = $input->VehNo ?? '';// '';
        $cover_note_no = $input->cover_note_no;//'D99999D-21000339';
        $email_to = $input->email_to;//'mrbigchiam@gmail.com';
        $data["agent_code"] = $agent_code;
        $data["VehNo"] = $VehNo;
        $data["cover_note_no"] = $cover_note_no;
        $data["email_to"] = $email_to;
        $path = 'ResendCoverNote';
        // Generate XML from view
        $xml = view('backend.xml.zurich.resend_cover_note')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }     
        $result_data = $result->response->ResendCoverNoteResponse->XmlResult;
        return new ResponseData([
            'response' => (object)$result_data
        ]); 
    }

    public function jpj_status(object $input) : object
    {
        $path = 'GetJPJStatus';
        $participant_code = $this->participant_code;
        $transaction_ref_no = $input->transaction_ref_no;
        $request_datetime = $input->request_datetime;
        $signature = array(
            'request_datetime' => $request_datetime,
            'transaction_ref_no' => $transaction_ref_no,
        );
        $hashed_signature = $this->generateSignature($signature);
        $data["participant_code"] = $participant_code;
        $data["transaction_reference"] = $transaction_ref_no;
        $data["request_datetime"] = $request_datetime;
        $data["hashcode"] = $hashed_signature;

        $agent_code = $this->agent_code;
        $cover_note_no = $input->cover_note_no;//'D02940-20000037';

        $data["agent_code"] = $agent_code;
        $data["cover_note_no"] = $cover_note_no;
        // Generate XML from view
        $xml = view('backend.xml.zurich.jpj_status')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        if (!$result->status) {
            return $this->abort($result->response, $result->code);
        }     
        $result_data = $result->response->GetJPJStatusResponse->XmlResult;
        $xml_data = simplexml_load_string($result_data);
        $response['JPJStatus']['CoverNoteNo'] = $xml_data->JPJStatus->CoverNoteNo ?? '';
        $response['JPJStatus']['VehRegNo'] = $xml_data->JPJStatus->VehRegNo ?? '';
        $response['JPJStatus']['JPJStatus'] = $xml_data->JPJStatus->JPJStatus ?? '';
        $response['JPJStatus']['DateTime'] = $xml_data->JPJStatus->DateTime ?? '';
        return new ResponseData([
            'response' => (object)$response
        ]); 
    }

    public function premiumDetails(object $input, $full_quote = false) : object
    {
        $quotation = (object)[
            'request_datetime' => "2017-Mar-17 11:00:00 PM",        
            'transaction_ref_no' => "020000008",
            'id' => "850321-07-5179",
            // 'VehNo' => "WA823H",
            'VehNo' => "WCA1451qwe",
            'getmail' => [
                'support@zurich.com.my',
                'noreply@zurich.com.my',
            ],
            'quotationNo' => '',
            'trans_type' => 'B',
            'pre_VehNo' => '',
            'product_code' => 'PZ01',
            'cover_type' => 'V-CO',
            'ci_code' => 'MX1',
            'eff_date' => '13/12/2022',
            'exp_date' => '12/12/2023',
            'new_owned_Veh_ind' => 'Y',
            'VehReg_date' => '10/03/2016',
            'ReconInd' => 'N',
            'modcarInd' => 'Y',
            'modperformanceaesthetic' => 'o',
            'modfunctional' => '2,128',
            'yearofmake' => '2010',
            'make' => '11',
            'model' => '11*1030',
            'capacity' => '1497',
            'uom' => 'CC',
            'engine_no' => 'EWE323WS',
            'chasis_no' => 'PM2L252S002107437',
            'logbook_no' => 'ERTGRET253',
            'reg_loc' => 'L',
            'region_code' => 'W',
            'no_of_passenger' => '5',
            'no_of_drivers' => '1',
            'ins_indicator' => 'P',
            'name' => 'TAN AI LING',
            'ins_nationality' => 'L',
            'new_ic' => '530102-06-5226',
            'other_id' ?? '',
            'date_of_birth' => '22/12/1998',
            'age' => '27',
            'gender' => 'M',
            'marital_sts' => 'M',
            'occupation' => '99',
            'mobile_no' => '012-3456789',
            'off_ph_no' => '03-45678900',
            'email' => 'zurich.api@gmail.com',
            'address' => '20, Jalan PJU, Taman A, Petaling Jaya',
            'postcode' => '50150',
            'state' => '06',
            'country' => 'MAS',
            'sum_insured' => '30000.00',
            'av_ind' => 'Y',
            'vol_excess' => '01',
            'pac_ind' => 'Y',
            'pac_type' => 'TAGPLUS PAC',
            'pac_unit' => '1',
            'all_driver_ind' => 'Y',
            'abisi' => '28000.00',
            'chosen_si_type' => 'REC_SI',
            'ext_cov_code' => '101',
            'unit_day' => '7',
            'unit_amount' => '50',
            'ecd_eff_date' => '14/1/2017',
            'ecd_exp_date' => '13/1/2018',
            'ecd_sum_insured' => '3000',
            'no_of_unit' => '1',
            'ecd_pac_code' => 'R0075',
            'ecd_pac_unit' => '1',
        ];
        $premium = $this->quotation($quotation);

        $extra_cover_list = [];
        foreach(self::EXTRA_COVERAGE_LIST as $_extra_cover_code) {
            $extra_cover = new ExtraCover([
                'selected' => false,
                'readonly' => false,
                'extra_cover_code' => $_extra_cover_code,
                'extra_cover_description' => $this->getExtraCoverDescription($_extra_cover_code),
                'premium' => 0,
                'sum_insured' => 0
            ]);
            
            $sum_insured_amount = 0;

            switch($_extra_cover_code) {
                case '01': 
                case '02': 
                case '03': 
                case '06': 
                case '07': 
                case '101': 
                case '103': 
                case '108': 
                case '109':
                case '111': 
                case '112':
                case '19':  
                case '22': 
                case '25': 
                case '57': 
                case '72': 
                case '89': 
                case '97': 
                case '200': 
                case '201': 
                case '202': 
                case '203': 
            }

            if(!empty($sum_insured_amount)) {
                $extra_cover->sum_insured = $sum_insured_amount;
            }

            array_push($extra_cover_list, $extra_cover);
        }
        // Include Extra Covers to Get Premium
        $input->extra_cover = $extra_cover_list;
        $total_benefit_amount = 0;

        if(!empty($premium->response->extra_coverage)) {
            foreach($input->extra_cover as $extra_cover) {
                foreach($premium->response->extra_coverage as $extra) {
                    if((string) $extra->ExtCoverCode === $extra_cover->extra_cover_code) {
                        $extra_cover->premium = formatNumber((float) $extra->ExtCoverPrem);
                        $total_benefit_amount += (float) $extra->ExtCoverPrem;
    
                        if(!empty($extra->ExtCoverSumInsured)) {
                            $extra_cover->sum_insured = formatNumber((float) $extra->ExtCoverSumInsured);
                        }
                    }
                }
            }
        }
        $premium_data = $premium->response->PremiumDetails;
        $response = new PremiumResponse([
            'act_premium' => formatNumber($premium_data['ActPrem']),
            'basic_premium' => formatNumber($premium_data['BasicPrem']),
            // 'detariff' => $premium_data->detariff,
            // 'detariff_premium' => formatNumber($premium_data->detariff_premium),
            // 'discount' => formatNumber($premium_data->discount),
            // 'discount_amount' => formatNumber($premium_data->discount_amount),
            'excess_amount' => formatNumber($premium_data['ExcessAmt']),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
            'gross_premium' => formatNumber($premium_data['GrossPrem']),
            'loading' => formatNumber($premium_data['LoadAmt']),
            'ncd_amount' => formatNumber($premium_data['NCDAmt']),
            'net_premium' => formatNumber($premium_data['NettPrem'] + $premium_data['GST_Amt'] + $premium_data['StampDutyAmt']),
            'sum_insured' => formatNumber($premium_data['sum_insured'] ?? 0),
            // 'min_sum_insured' => formatNumber($vehicle_vix->response->min_sum_insured ?? $vehicle->min_sum_insured),
            // 'max_sum_insured' => formatNumber($vehicle_vix->response->max_sum_insured ?? $vehicle->max_sum_insured),
            // 'sum_insured_type' => $vehicle->sum_insured_type,
            'min_sum_insured' => formatNumber(0),
            'max_sum_insured' => formatNumber(0),
            'sum_insured_type' => '',
            'sst_amount' => formatNumber($premium_data['GST_Amt']),
            'sst_percent' => formatNumber(ceil(($premium_data['GST_Amt'] / $premium_data['GrossPrem']) * 100)),
            'stamp_duty' => formatNumber($premium_data['StampDutyAmt']),
            // 'tariff_premium' => formatNumber($premium_data->tariff_premium),
            'total_benefit_amount' => formatNumber($total_benefit_amount),
            'total_payable' => formatNumber($premium_data['TtlPayablePremium']),
            'named_drivers_needed' => false,
        ]);
        
        return (object) [
            'status' => true,
            'response' => $response
        ];
    }

    private function getExtraCoverDescription(string $extra_cover_code) : string
    {
        $extra_cover_name = '';

        switch($extra_cover_code) {
            case '01': { 
                $extra_cover_name = 'All Drivers';
                break;
            }
            case '02': { 
                $extra_cover_name = 'Legal Liability to Passengers';
                break;
            }
            case '03': { 
                $extra_cover_name = 'All Riders';
                break;
            }
            case '06': { 
                $extra_cover_name = 'Tuition';
                break;
            }
            case '07': { 
                $extra_cover_name = 'Additional Drivers';
                break;
            }
            case '101': { 
                $extra_cover_name = 'Extension of Kindom of Thailand';
                break;
            }
            case '103': { 
                $extra_cover_name = 'Malicious Damage';
                break;
            }
            case '108': { 
                $extra_cover_name = 'Passenger Liability Cover';
                break;
            }
            case '109': { 
                $extra_cover_name = 'Ferry Transit To and/or Sabah And The Federal';
                break;
            }
            case '111': { 
                $extra_cover_name = 'Current Year NCD Relief (Comp Private Car)';
                break;
            }
            case '112': { 
                $extra_cover_name = 'Cart';
                break;
            }
            case '19': { 
                $extra_cover_name = 'Passenger Risk';
                break;
            }
            case '22': { 
                $extra_cover_name = 'Caravan / Luggage / Trailers (Private Car Only)';
                break;
            }
            case '25': { 
                $extra_cover_name = 'Strike Riot & Civil Commotion';
                break;
            }
            case '57': { 
                $extra_cover_name = 'Inclusion Of Special Perils';
                break;
            }
            case '72': { 
                $extra_cover_name = 'Legal Liability Of Passengers For Negligent Acts';
                break;
            }
            case '89': { 
                $extra_cover_name = 'Breakage Of Glass In WindScreen, Window Or Sunroof';
                break;
            }
            case '97': { 
                $extra_cover_name = 'Vehicle Accessories Endorsement';
                break;
            }
            case '200': { 
                $extra_cover_name = 'PA Basic';
                break;
            }
            case '201': { 
                $extra_cover_name = 'Temporary Courtesy Car';
                break;
            }
            case '202': { 
                $extra_cover_name = 'Towing And Cleaning Due To Water Damage';
                break;
            }
            case '203': { 
                $extra_cover_name = 'Key Replacement';
                break;
            }
        }

        return $extra_cover_name;
    }

    private function sortExtraCoverList(array $extra_cover_list) : array
    {
        foreach ($extra_cover_list as $_extra_cover) {
            $sequence = 99;
            switch ((array)$_extra_cover->extra_cover_code) {
                case '01': { // All Drivers
                    $sequence = 1;
                    break;
                }
                case '02': { // Legal Liability to Passengers
                    $sequence = 2;
                    break;
                }
                case '03': { // All Riders
                    $sequence = 3;
                    break;
                }
                case '06': { // Tuition
                    $sequence = 4;
                    break;
                }
                case '07': { // Additional Drivers
                    $sequence = 5;
                    break;
                }
                case '101': { // Extension of Kindom of Thailand
                    $sequence = 6;
                    break;
                }
                case '103': { // Malicious Damage
                    $sequence = 7;
                    break;
                }
                case '108': { // Passenger Liability Cover
                    $sequence = 8;
                    break;
                }
                case '109': { // Ferry Transit To and/or Sabah And The Federal
                    $sequence = 9;
                    break;
                }
                case '111': { // Current Year NCD Relief (Comp Private Car)
                    $sequence = 10;
                    break;
                }
                case '112': { // Cart
                    $sequence = 11;
                    break;
                }
                case '19': { // Passenger Risk
                    $sequence = 12;
                    break;
                }
                case '22': { // Caravan / Luggage / Trailers (Private Car Only) 
                    $sequence = 15;
                    break;
                }
                case '25': { // Strike Riot & Civil Commotion
                    $sequence = 16;
                    break;
                }
                case '57': { // Inclusion Of Special Perils 
                    $sequence = 19;
                    break;
                }
                case '72': { // Legal Liability Of Passengers For Negligent Acts
                    $sequence = 20;
                    break;
                }
                case '89': { // Breakage Of Glass In WindScreen, Window Or Sunroof 
                    $sequence = 21;
                    break;
                }
                case '97': { // Vehicle Accessories Endorsement 
                    $sequence = 23;
                    break;
                }
                case '200': { // PA Basic 
                    $sequence = 28;
                    break;
                }
                case '201': { // Temporary Courtesy Car
                    $sequence = 29;
                    break;
                }
                case '202': { // Towing And Cleaning Due To Water Damage 
                    $sequence = 30;
                    break;
                }
                case '203': { // Key Replacement
                    $sequence = 31;
                    break;
                }
            }
            
            $_extra_cover->sequence = $sequence;
        }
        $sorted = array_values(Arr::sort($extra_cover_list, function ($value) {
            return $value->sequence;
        }));

        return $sorted;
    }

    public function submission(object $input) : object
    {

    }

    public function abort(string $message, int $code = 490) : ResponseData
    {
        return new ResponseData([
            'status' => false,
            'response' => $message,
            'code' => $code
        ]);
    }

    private function getVIXNCD(object $input) : ResponseData
    {
        $path = 'GetVehicleInfo';
        $request_datetime = $input->request_datetime;
        $id = $input->id;
        $VehNo = $input->VehNo;
        $signature = array(
            'request_datetime' => $request_datetime,
            'id' => $id,
            'VehNo' => $VehNo,
        );
        $sign_type = "vehinfo";
        $data["data"] = "{'AgentCode':'".$this->agent_code.
            "','ParticipantCode':'".$this->participant_code.
            "','RequestDateTime':'".$request_datetime.
            "','ID':'".$id.
            "','VehNo':'".$VehNo.
            "','Signature':'".$this->generateSignature($signature, $sign_type)."'}";
        // Generate XML from view
        $xml = view('backend.xml.zurich.veh_input_info')->with($data)->render();
        // Call API
        $result = $this->cURL($path, $xml);
        if(!$result->status) {
            return $this->abort($result->response);
        }
        $result_data = $result->response->GetVehicleInfoResponse->GetVehicleInfoResult;
        // Check for Error
        $error = '';
        $NxtNCDEffDate = '';
        if((string)$result_data->RespCode == '000'){
            //next NCD effective date
            $date_dmy = str_split((string)$result_data->NxtNCDEffDate, 2);
            $NxtNCDEffDate = $date_dmy[0].'-'.$date_dmy[1].'-'.$date_dmy[2].$date_dmy[3];
        }
        else {
            $error = $result_data->RespDesc;
        }
        $response = (object) [
            'VehRegNo' => (string) $result_data->VehRegNo,
            'VehClass' => (string) $result_data->VehClass,
            'VehMake' => (string)$result_data->VehMake,
            'VehMakeYear' => (string) $result_data->VehMakeYear,
            'VehModel' => (string) $result_data->VehModel,
            'VehModelCode' => (string) $result_data->VehModelCode,
            'VehSeat' => (string) $result_data->VehSeat,
            'VehTransType' => (string) $result_data->VehTransType,
            'VehCC' => (string) $result_data->VehCC,
            'VehUse' => (string) $result_data->VehUse,
            'VehEngineNo' => (string) $result_data->VehEngineNo,
            'VehChassisNo' => (string) $result_data->VehChassisNo,
            'VehFuelType' => (string) $result_data->VehFuelType,
            'MarketValue' => (string) $result_data->MarketValue,
            'NVIC' => (string) $result_data->NVIC,
            'PreInsCode' => (string) $result_data->VIXPreInsCode,
            'CoverType' => (string) $result_data->VIXCoverType,
            'PolExpDate' => (string) $result_data->PolExpDate,
            'BuiltType' => (string) $result_data->BuiltType,
            'NCDPct' => (string) $result_data->NCDPct,
            'NxtNCDEffDate' => (string)$NxtNCDEffDate,
            'NCDStatus' => (string) $result_data->NCDStatus,
            'RespCode' => (string) $result_data->RespCode,
            'Error' => $error,
            'ISMNCDRespCode' => (string) $result_data->ISMNCDRespCode
        ];

        return new ResponseData([
            'response' => $response,
        ]);
    }
    public function cURL(string $path, string $xml, string $soap_action = null, string $method = 'POST', array $header = []) : ResponseData
    {
        // Concatenate URL
        $url = $this->host_vix . '?wsdl';
        // Check XML Error
        libxml_use_internal_errors(true);

        // Construct API Request
        $request_options = [
            'timeout' => 60,
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
                'Accept' => 'text/xml; charset=utf-8',
                'SOAPAction' => self::SOAP_ACTION_DOMAIN .'/'. $path
            ],
            'body' => $xml
        ];

        $result = HttpClient::curl($method, $url, $request_options);

        if($result->status) {
            $cleaned_xml = preg_replace('/(<\/|<)[a-zA-Z]+:([a-zA-Z0-9]+[ =>])/', '$1$2', $result->response);
            $response = simplexml_load_string($cleaned_xml);
            if($response === false) {
                return $this->abort(__('api.xml_error'));
            }

            $response = $response->xpath('Body')[0];
        } else {
            $message = '';
            if(empty($result->response)) {
                $message = __('api.empty_response', ['company' => $this->company_name]);
            } else {
                if(is_string($result->response)) {
                    $message = $result->response;
                } else {
                    $message = 'An Error Encountered. ' . json_encode($result->response);
                }
            }

            return $this->abort($message);
        }

        return new ResponseData([
            'status' => $result->status,
            'response' => $response
        ]);
    }
}