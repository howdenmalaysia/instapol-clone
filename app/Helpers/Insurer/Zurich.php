<?php

namespace App\Helpers\Insurer;

use App\DataTransferObjects\Motor\ExtraCover;
use App\DataTransferObjects\Motor\VariantData;
use App\DataTransferObjects\Motor\Vehicle;
use App\DataTransferObjects\Motor\Response\ResponseData;
use App\DataTransferObjects\Motor\Response\PremiumResponse;
use App\DataTransferObjects\Motor\Response\VIXNCDResponse;
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
    private string $agent_code;
    private string $password;
    private string $secret_key;

    private const SOAP_ACTION_DOMAIN = 'https://gtws2.zurich.com.my/ziapps/zurichinsurance/services';
    private const EXTRA_COVERAGE_LIST = ['01','02','03','06','07','101','103','108','109','111',
    '112','19','20E','20W','22','25','25E','25W','57','72','89','89A','97','97A','D1','TW1','TW2',
    '200','201','202','203','01A'];
    private const MIN_SUM_INSURED = 10000;
    private const MAX_SUM_INSURED = 500000;
    private const OCCUPATION = '99';

	public function __construct(int $insurer_id, string $insurer_name)
    {
        $this->company_id = $insurer_id;
        $this->company_name = $insurer_name;
        
		$this->host_vix = config('insurer.config.zurich_config.host_vix');
		$this->agent_code = config('insurer.config.zurich_config.agent_code');
		$this->secret_key = config('insurer.config.zurich_config.secret_key');
		$this->participant_code = config('insurer.config.zurich_config.participant_code');
	}

    public function vehInputMake(object $input) : object
    {
        $path = 'GetVehicleMake';
        $request_datetime = Carbon::now()->format('Y-M-d h:i:s A');
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

    private function getModelDetails(object $input) : object
    {
        $path = 'GetVehicleModel';
        $request_datetime = Carbon::now()->format('Y-M-d h:i:s A');
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
        $data = [
            'id_number' => $input->id_number,
            'vehicle_number' => $input->vehicle_number
        ];

        $vix = $this->getVIXNCD($data);
        if(!$vix->status && is_string($vix->response)) {
            return $this->abort($vix->response);
        }
        
        $get_inception = str_split($vix->response->NxtPolEffDate, 2);
        $inception_date =  $get_inception[2] . $get_inception[3] . "-" . $get_inception[1] .  "-" . $get_inception[0];
        $get_expiry = str_split($vix->response->NxtPolExpDate, 2);
        $expiry_date =  $get_expiry[2] . $get_expiry[3] . "-" . $get_expiry[1] .  "-" . $get_expiry[0];
        
        $today = Carbon::today()->format('Y-m-d');
        // 1. Check inception date
        if($inception_date < $today) {
            return $this->abort('inception date expired');
        }

        // 2. Check Sum Insured -> market price
        $sum_insured = formatNumber($vix->response->MarketValue, 0);
        $sum_insured_type = "Makert Value";
        if ($sum_insured < self::MIN_SUM_INSURED || $sum_insured > self::MAX_SUM_INSURED) {
            return $this->abort(
                __('api.sum_insured_referred_between', ['min_sum_insured' => self::MIN_SUM_INSURED, 'max_sum_insured' => self::MAX_SUM_INSURED]),
                config('setting.response_codes.sum_insured_referred')
            );
        }

        $nvic = explode('|', (string) $vix->response->NVIC);
        //getting model
        $vehInputModel = (object)[      
            'product_code' => "PZ01",
            'make_year' => $vix->response->VehMakeYear,
            'make' => $vix->response->VehMake,
            'filter_key' => '',
        ];
        $variants = [];
        $BodyType = '';
        $uom = '';
        foreach($nvic as $_nvic) {
            // Get Vehicle Details
            $details = $this->getModelDetails($vehInputModel);
            $get_variant = $vix->response->VehTransType;
            foreach($details->response->Model as $model_details){
                if($model_details['sdfVehModelID'] == $vix->response->VehModelCode){
                    $get_variant = $model_details['Description'];
                    $BodyType = $model_details['BodyType'];
                    $uom = $model_details['UOM'];
                }
            }
            
            array_push($variants, new VariantData([
                'nvic' => $_nvic,
                'sum_insured' => floatval($sum_insured),
                'variant' => $get_variant,
            ]));
        }
        // Get Vehicle Details
        $vehicle_make = $this->vehInputMake($vehInputModel);
        $make = '';
        foreach($vehicle_make->response->Make as $make_details){
            if($make_details['sdfMakeID'] == $vix->response->VehMake){
                $make = $make_details['Description'];
            }
        }
        return (object) [
            'status' => true,
            'veh_model_code' => $vix->response->VehModelCode,
            'uom' => $uom,
            'response' => new VIXNCDResponse([
                'body_type_code' => intval($this->body_type_code($BodyType)) ?? null,
                'body_type_description' => $BodyType ?? null,
                'chassis_number' => $vix->response->VehChassisNo,
                'coverage' => $vix->response->CoverType,
                'engine_capacity' => intval($vix->response->VehCC),
                'engine_number' => $vix->response->VehEngineNo,
                'expiry_date' => Carbon::parse($expiry_date)->format('d M Y'),
                'inception_date' => Carbon::parse($inception_date)->format('d M Y'),
                'make' => $make ?? '',
                'make_code' => intval($vix->response->VehMake),
                'model' => $vix->response->VehModel ?? '',
                'model_code' => null,
                'manufacture_year' => intval($vix->response->VehMakeYear),
                'max_sum_insured' => doubleval(self::MAX_SUM_INSURED),
                'min_sum_insured' => doubleval(self::MIN_SUM_INSURED),
                'sum_insured' => $sum_insured,
                'sum_insured_type' => 'Market Value',
                'ncd_percentage' => floatval($vix->response->NCDPct),
                'seating_capacity' => intval($vix->response->VehSeat),
                'variants' => $variants,
                'vehicle_number' => $vix->response->VehRegNo,
            ])
        ];
    }

    public function quotation(object $input) : object
    {
        $data = (object) [
			'vehicle_number' => $input->vehicle_number,
			'id_type' => $input->id_type,
			'id_number' => $input->id_number,
			'gender' => $input->gender,
			'marital_status' => $input->marital_status,
			'region' => $input->region,
			'vehicle' => $input->vehicle,
			'extra_cover' => $input->extra_cover,
			'email' => $input->email,
			'phone_number' => $input->phone_number,
			'nvic' => $input->vehicle->nvic,
			'unit_no' => $input->unit_no ?? '',
			'building_name' => $input->building_name ?? '',
			'address_one' => $input->address_one,
			'address_two' => $input->address_two ?? '',
			'city' => $input->city,
			'postcode' => $input->postcode,
			'state' => $input->state,
			'occupation' => $input->occupation,
		];

		$result = $this->premiumDetails($data);

		if (!$result->status) {
			return $this->abort($result->response);
		}

		$result->response->quotation_number = $result->response->quotation_number;

		return (object) [
			'status' => true,
			'response' => $result->response
		];
    }

    public function getQuotation(object $input) : object
    {
        //participant
        $participant_code = $this->participant_code;
        $transaction_ref_no = $input->transaction_ref_no;
        $request_datetime = $input->request_datetime;
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
        $trans_type = $input->trans_type;
        $VehNo = $input->VehNo;
        $pre_VehNo = $input->pre_VehNo;
        $product_code = $input->product_code;
        $cover_type = $input->cover_type;
        $ci_code = $input->ci_code;
        $eff_date = $input->eff_date;
        $exp_date = $input->exp_date;
        $new_owned_Veh_ind = $input->new_owned_Veh_ind;
        $VehReg_date = $input->VehReg_date;
        $ReconInd = $input->ReconInd;
        $modcarInd = $input->modcarInd;
        $modperformanceaesthetic = $input->modperformanceaesthetic;
        $modfunctional = $input->modfunctional;
        $yearofmake = $input->yearofmake;
        $make = $input->make;
        $model = $input->model;
        $capacity = $input->capacity;
        $uom = $input->uom;
        $engine_no = $input->engine_no;
        $chasis_no = $input->chasis_no;
        $logbook_no = $input->logbook_no;
        $reg_loc = $input->reg_loc;
        $region_code = $input->region_code;
        $no_of_passenger = $input->no_of_passenger;
        $no_of_drivers = $input->no_of_drivers;
        $ins_indicator = $input->ins_indicator;
        $name = $input->name;
        $ins_nationality = $input->ins_nationality;
        $new_ic = $input->new_ic;
        $other_id = $input->other_id ?? '';
        if($new_ic == ''){
            if($other_id == ''){
                return 'Others ID cannot be blank if New IC Number is blank';
            }
        }
        $date_of_birth = $input->date_of_birth;
        $age = $input->age;
        $gender = $input->gender;
        $marital_sts = $input->marital_sts;
        $occupation = $input->occupation;
        $mobile_no = $input->mobile_no;
        $off_ph_no = $input->off_ph_no;
        $email = $input->email;
        $address = $input->address;
        $postcode = $input->postcode;
        $state = $input->state;
        $country = $input->country;
        $sum_insured = $input->sum_insured;
        $av_ind = $input->av_ind;
        $vol_excess = $input->vol_excess;
        $pac_ind = $input->pac_ind;
        $pac_type = $input->pac_type;
        $pac_unit = $input->pac_unit;
        $all_driver_ind = $input->all_driver_ind;
        $abisi = $input->abisi;
        $chosen_si_type = $input->chosen_si_type;

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
        $ext_cov_code = $input->ext_cov_code;
        $unit_day = $input->unit_day;
        $unit_amount = $input->unit_amount;
        $ecd_eff_date = $input->ecd_eff_date;
        $ecd_exp_date = $input->ecd_exp_date;
        $ecd_sum_insured = $input->ecd_sum_insured;
        $no_of_unit = $input->no_of_unit;

        $data["ext_cov_code"] = $ext_cov_code;
        $data["unit_day"] = $unit_day;
        $data["unit_amount"] = $unit_amount;
        $data["ECD_eff_date"] = $ecd_eff_date;
        $data["ECD_exp_date"] = $ecd_exp_date;
        $data["ECD_sum_insured"] = $ecd_sum_insured;
        $data["no_of_unit"] = $no_of_unit;

        //PAC extra cover details
        $ecd_pac_code = $input->ecd_pac_code;
        $ecd_pac_unit = $input->ecd_pac_unit;

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
        $MotorExtraCoverDetails = [];
        foreach($xml_data->ExtraCoverData as $value){
            $MotorExtraCoverDetails[$index]['ExtCoverCode'] = (string)$value->ExtCoverCode;
            $MotorExtraCoverDetails[$index]['ExtCoverPrem'] = (string)$value->ExtCoverPrem;
            $MotorExtraCoverDetails[$index]['ExtCoverSumInsured'] = (string)$value->ExtCoverSumInsured;
            $MotorExtraCoverDetails[$index]['Compulsory_Ind'] = (string)$value->Compulsory_Ind;
            $MotorExtraCoverDetails[$index]['sequence'] = '';
            $index++;
        }
        $response = (object)[
            'PremiumDetails' => [
                'BasicPrem' => $xml_data->PremiumDetails->BasicPrem,
                'LoadPct' => $xml_data->PremiumDetails->LoadPct,
                'LoadAmt' => $xml_data->PremiumDetails->LoadAmt,
                'TuitionLoadPct' => $xml_data->PremiumDetails->TuitionLoadPct,
                'TuitionLoadAmt' => $xml_data->PremiumDetails->TuitionLoadAmt,
                'AllRiderAmt' => $xml_data->PremiumDetails->AllRiderAmt,
                'NCDAmt' => $xml_data->PremiumDetails->NCDAmt,
                'VolExcessDiscPct' => $xml_data->PremiumDetails->VolExcessDiscPct,
                'VolExcessDiscAmt' => $xml_data->PremiumDetails->VolExcessDiscAmt,
                'TotBasicNetAmt' => $xml_data->PremiumDetails->TotBasicNetAmt,
                'TotExtCoverPrem' => $xml_data->PremiumDetails->TotExtCoverPrem,
                'RnwBonusPct' => $xml_data->PremiumDetails->RnwBonusPct,
                'RnwBonusAmt' => $xml_data->PremiumDetails->RnwBonusAmt,
                'GrossPrem' => $xml_data->PremiumDetails->GrossPrem,
                'CommPct' => $xml_data->PremiumDetails->CommPct,
                'CommAmt' => $xml_data->PremiumDetails->CommAmt,
                'RebatePct' => $xml_data->PremiumDetails->RebatePct,
                'RebateAmt' => $xml_data->PremiumDetails->RebateAmt,
                'GST_Pct' => $xml_data->PremiumDetails->GST_Pct,
                'GST_Amt' => $xml_data->PremiumDetails->GST_Amt,
                'StampDutyAmt' => $xml_data->PremiumDetails->StampDutyAmt,
                'TotMtrPrem' => $xml_data->PremiumDetails->TotMtrPrem,
                'NettPrem' => $xml_data->PremiumDetails->NettPrem,
                'BasicAnnualPrem' => $xml_data->PremiumDetails->BasicAnnualPrem,
                'ActPrem' => $xml_data->PremiumDetails->ActPrem,
                'NonActPrem' => $xml_data->PremiumDetails->NonActPrem,
                'ExcessType' => $xml_data->PremiumDetails->ExcessType,
                'ExcessAmt' => $xml_data->PremiumDetails->ExcessAmt,
                'TotExcessAmt' => $xml_data->PremiumDetails->TotExcessAmt,
                'PAC_SumInsured' => $xml_data->PremiumDetails->PAC_SumInsured,
                'PAC_Prem' => $xml_data->PremiumDetails->PAC_Prem,
                'PAC_AddPrem' => $xml_data->PremiumDetails->PAC_AddPrem,
                'PAC_GrossPrem' => $xml_data->PremiumDetails->PAC_GrossPrem,
                'PAC_GSTPct' => $xml_data->PremiumDetails->PAC_GSTPct,
                'PAC_GSTAmt' => $xml_data->PremiumDetails->PAC_GSTAmt,
                'PAC_StampDuty' => $xml_data->PremiumDetails->PAC_StampDuty,
                'PAC_CommPct' => $xml_data->PremiumDetails->PAC_CommPct,
                'PAC_CommAmt' => $xml_data->PremiumDetails->PAC_CommAmt,
                'PAC_RebatePct' => $xml_data->PremiumDetails->PAC_RebatePct,
                'PAC_RebateAmt' => $xml_data->PremiumDetails->PAC_RebateAmt,
                'PAC_TotPrem' => $xml_data->PremiumDetails->PAC_TotPrem,
                'PAC_NettPrem' => $xml_data->PremiumDetails->PAC_NettPrem,
                'TtlPayablePremium' => $xml_data->PremiumDetails->TtlPayablePremium,
                'GstOnCommAmt' => $xml_data->PremiumDetails->GstOnCommAmt,
                'PAC_GstOnCommAmt' => $xml_data->PremiumDetails->PAC_GstOnCommAmt,
                'Quote_Exp_Date' => $xml_data->PremiumDetails->Quote_Exp_Date,
                'Chosen_Vehicle_SI_Lower_Bound' => $xml_data->PremiumDetails->Chosen_Vehicle_SI_Lower_Bound,
                'Chosen_Vehicle_SI_Upper_Bound' => $xml_data->PremiumDetails->Chosen_Vehicle_SI_Upper_Bound,
                'AV_SI_Value' => $xml_data->PremiumDetails->AV_SI_Value,
                'Rec_SI_Value' => $xml_data->PremiumDetails->Rec_SI_Value,
            ],
            'MotorExtraCoverDetails' => $MotorExtraCoverDetails,
            'ReferralDetails' => [
                'ReferralCode' => $xml_data->ReferralData->Referral_Decline_Code,
                'ReferralMessage' => $xml_data->ReferralData->Referral_Message,
            ],
            'ErrorDetails' => [
                'ErrorCode' => $xml_data->Error_Display->Error_Code,
                'ErrorDesc' => $xml_data->Error_Display->Error_Desc,
                'Remarks' => $xml_data->Error_Display->Remarks,
                'WarningInd' => $xml_data->Error_Display->Warning_Ind,
            ],
            'QuotationInfo' => [
                'QuotationNo' => $xml_data->QuotationInfo->QuotationNo,
                'NCDMsg' => $xml_data->QuotationInfo->NCDMsg,
                'NCDPct' => $xml_data->QuotationInfo->NCDPct,
            ]
        ];

        
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
        $vehicle = $input->vehicle ?? null;
        $ncd_amount = $basic_premium = $total_benefit_amount = $gross_premium = $sst_percent = $sst_amount = $stamp_duty = $excess_amount = $total_payable = 0;
        $pa = null;

        if ($full_quote) {
            $vehicle_vix = $this->vehicleDetails($input);
            if (!$vehicle_vix->status) {
                return $this->abort($vehicle_vix->response, $vehicle_vix->code);
            }
            // Get Selected Variant
            $selected_variant = null;
            if ($input->nvic == '-') {
                if (count($vehicle_vix->response->variants) == 1) {
                    $selected_variant = $vehicle_vix->response->variants[0];
                }
            } else {
                foreach ($vehicle_vix->response->variants as $_variant) {
                    if ($input->nvic == $_variant->nvic) {
                        $selected_variant = $_variant;
                        break;
                    }
                }
            }

            if (empty($selected_variant)) {
                return $this->abort(trans('api.variant_not_match'));
            }

            // set vehicle
            $vehicle = new Vehicle([
                'make' => $vehicle_vix->response->make,
                'model' => $vehicle_vix->response->model,
                'nvic' => $selected_variant->nvic,
                'variant' => $selected_variant->variant,
                'engine_capacity' => $vehicle_vix->response->engine_capacity,
                'manufacture_year' => $vehicle_vix->response->manufacture_year,
                'ncd_percentage' => $vehicle_vix->response->ncd_percentage,
                'coverage' => $vehicle_vix->response->coverage,
                'inception_date' => $vehicle_vix->response->inception_date,
                'expiry_date' => $vehicle_vix->response->expiry_date,
                'sum_insured_type' => $vehicle_vix->response->sum_insured_type,
                'sum_insured' => $vehicle_vix->response->sum_insured,
                'min_sum_insured' => $vehicle_vix->response->min_sum_insured,
                'max_sum_insured' => $vehicle_vix->response->max_sum_insured,
                'extra_attribute' => (object) [
                    'chassis_number' => $vehicle_vix->response->chassis_number,
                    'cover_type' => $vehicle_vix->response->cover_type,
                    'engine_number' => $vehicle_vix->response->engine_number,
                    'seating_capacity' => $vehicle_vix->response->seating_capacity,
                ],
            ]);

            $dobs = str_split($input->id_number, 2);
            $id_number = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
            $year = intval($dobs[0]);
            if ($year >= 10) {
                $year += 1900;
            } else {
                $year += 2000;
            }
            $dob = $dobs[2] . "-" . $dobs[1] . "-" . strval($year);
            $region = '';
            if($input->region == 'West'){
                $region = 'W';
            }
            else if($input->region == 'East'){
                $region = 'E';
            }
            $quotation = (object)[
                'request_datetime' => Carbon::now()->format('Y/M/d h:i:s A'),
                'transaction_ref_no' => $this->participant_code."0000008",//
                'VehNo' => $input->vehicle_number,
                'getmail' => [
                    'support@zurich.com.my',
                    'noreply@zurich.com.my',
                ],
                'quotationNo' => '',
                'trans_type' => 'B',
                'pre_VehNo' => $vehicle_vix->response->vehicle_number,
                'product_code' => 'PZ01',
                'cover_type' => 'V-CO',
                'ci_code' => 'MX1',
                'eff_date' => Carbon::parse($vehicle_vix->response->inception_date)->format('d/m/Y') ?? Carbon::now()->format('d/m/Y'),
                'exp_date' => Carbon::parse($vehicle_vix->response->expiry_date)->format('d/m/Y') ?? Carbon::now()->addYear()->subDay()->format('d/m/Y'),
                'new_owned_Veh_ind' => '',
                'VehReg_date' => '10/03/2016',
                'ReconInd' => '',
                'modcarInd' => 'Y',
                'modperformanceaesthetic' => 'o',
                'modfunctional' => '2,128',
                'yearofmake' => $vehicle_vix->response->manufacture_year,
                'make' => $vehicle_vix->response->make_code,
                'model' => $vehicle_vix->veh_model_code,
                'capacity' => $vehicle_vix->response->engine_capacity,
                'uom' => $vehicle_vix->uom,
                'engine_no' => $vehicle_vix->response->engine_number,
                'chasis_no' => $vehicle_vix->response->chassis_number,
                'logbook_no' => '',
                'reg_loc' => 'L',
                'region_code' => $region,
                'no_of_passenger' => $vehicle_vix->response->seating_capacity,
                'no_of_drivers' => '1',
                'ins_indicator' => 'P',
                'name' => $input->name ?? 'TAN AI LING',
                'ins_nationality' => 'L',
                'new_ic' => $id_number,
                'other_id' => '',
                'date_of_birth' => $dob,
                'age' => $input->age,
                'gender' => $input->gender,
                'marital_sts' => $input->marital_status,
                'occupation' => self::OCCUPATION,
                'mobile_no' => $input->phone_number,
                'off_ph_no' => '',
                'email' => $input->email,
                'address' => $input->address_one . $input->address_two,
                'postcode' => $input->postcode,
                'state' => $this->getStateCode($input->state),
                'country' => 'MAS',
                'sum_insured' => $vehicle_vix->response->sum_insured,
                'av_ind' => 'Y',
                'vol_excess' => '',
                'pac_ind' => 'N',
                'pac_type' => 'TAGPLUS PAC',
                'pac_unit' => '1',
                'all_driver_ind' => 'Y',
                'abisi' => '25000.00',
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
            $premium = $this->getQuotation($quotation);

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
                    case '101': {
                        $sum_insured_amount = $vehicle_vix->response->sum_insured;
                        break;
                    }
                    case '103': 
                    case '108': 
                    case '109':
                    case '111': 
                    case '112':
                    case '19':  
                    case '20E':  
                    case '20W':  
                    case '22': {
                        $sum_insured_amount = 1500;
                        break;
                    }
                    case '25': 
                    case '25E': 
                    case '25W': 
                    case '57': 
                    case '72': 
                    case '89': {
                        $sum_insured_amount = 1000;
                        break;
                    }
                    case '89A': {
                        $sum_insured_amount = 1000;
                        break;
                    }
                    case '97': {
                        $sum_insured_amount = 500;
                        break;
                    }
                    case '97A': {
                        $sum_insured_amount = 2000;
                        break;
                    }
                    case 'D1': 
                    case 'TW1': 
                    case 'TW2': 
                    case '200': 
                    case '201': 
                    case '202': {
                        $sum_insured_amount = 1000;
                        break;
                    }
                    case '203': 
                    case '01A': 
                }

                if(!empty($sum_insured_amount)) {
                    $extra_cover->sum_insured = $sum_insured_amount;
                }

                array_push($extra_cover_list, $extra_cover);
            }
            // Include Extra Covers to Get Premium
            $input->extra_cover = $extra_cover_list;
        }

        if(!empty($premium->response->MotorExtraCoverDetails)) {
            foreach($input->extra_cover as $extra_cover) {
                foreach($premium->response->MotorExtraCoverDetails as $extra) {
                    if((string) $extra['ExtCoverCode'] === $extra_cover->extra_cover_code) {
                        $extra_cover->ExtCoverPrem = formatNumber((float) $extra['ExtCoverPrem']);
                        $total_benefit_amount += (float) $extra['ExtCoverPrem'];
    
                        if(!empty($extra['ExtCoverSumInsured'])) {
                            $extra_cover->sum_insured = formatNumber((float) $extra['ExtCoverSumInsured']);
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
            'discount' => formatNumber($premium_data['VolExcessDiscPct']),
            'discount_amount' => formatNumber($premium_data['VolExcessDiscAmt']),
            'excess_amount' => formatNumber($premium_data['ExcessAmt']),
            'extra_cover' => $this->sortExtraCoverList($input->extra_cover),
            'gross_premium' => formatNumber($premium_data['GrossPrem']),
            'loading' => formatNumber($premium_data['LoadAmt']),
            'ncd_amount' => formatNumber($premium_data['NCDAmt']),
            'net_premium' => formatNumber($premium_data['NettPrem'] + $premium_data['GST_Amt'] + $premium_data['StampDutyAmt']),
            'sum_insured' => formatNumber($vehicle_vix->response->sum_insured ?? 0),
            'min_sum_insured' => formatNumber($vehicle_vix->response->min_sum_insured),
            'max_sum_insured' => formatNumber($vehicle_vix->response->max_sum_insured),
            'sum_insured_type' => $vehicle_vix->response->sum_insured_type,
            'sst_amount' => formatNumber($premium_data['GST_Amt']),
            'sst_percent' => formatNumber(ceil(($premium_data['GST_Amt'] / $premium_data['GrossPrem']) * 100)),
            'stamp_duty' => formatNumber($premium_data['StampDutyAmt']),
            // 'tariff_premium' => formatNumber($premium_data->tariff_premium),
            'total_benefit_amount' => formatNumber($total_benefit_amount),
            'total_payable' => formatNumber($premium_data['TtlPayablePremium']),
            'named_drivers_needed' => false,
        ]);
        
        if($full_quote) {
            // Revert to premium without extra covers
            $response->excess_amount = $excess_amount;
            $response->basic_premium = $basic_premium;
            $response->ncd = $ncd_amount;
            $response->gross_premium = $gross_premium;
            $response->stamp_duty = $stamp_duty;
            $response->sst_amount = $sst_amount;
            $response->sst_percent = $sst_percent;
            $response->total_benefit_amount = 0;
            $response->total_payable = $total_payable;

            $response->vehicle = $vehicle;
        }
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
            case '20E': { 
                $extra_cover_name = 'Passenger Risk - Motor Trade';
                break;
            }
            case '20W': { 
                $extra_cover_name = 'Passenger Risk - Motor Trade';
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
            case '25E': { 
                $extra_cover_name = 'Strike Riot & Civil Commotion';
                break;
            }
            case '25W': { 
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
            case '89A': { 
                $extra_cover_name = 'Breakage Of Glass In WindScreen, Window Sunroof';
                break;
            }
            case '97': { 
                $extra_cover_name = 'Vehicle Accessories Endorsement';
                break;
            }
            case '97A': { 
                $extra_cover_name = 'Gas Conversion Kit Tank';
                break;
            }
            case 'D1': { 
                $extra_cover_name = 'Demonstration';
                break;
            }
            case 'TW1': { 
                $extra_cover_name = 'Inclusion Of Third Party';
                break;
            }
            case 'TW2': { 
                $extra_cover_name = 'Inclusion Of Third Party Working Risk - All';
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
            case '01A': {
                $extra_cover_name = 'Authorised Driver';
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
                case '20E': { // Passenger Risk - Motor Trade
                    $sequence = 13;
                    break;
                }
                case '20W': { // Passenger Risk - Motor Trade
                    $sequence = 14;
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
                case '25E': { // Strike Riot & Civil Commotion
                    $sequence = 17;
                    break;
                }
                case '25W': { // Strike Riot & Civil Commotion
                    $sequence = 18;
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
                case '89A': { // Breakage Of Glass In WindScreen, Window Sunroof 
                    $sequence = 22;
                    break;
                }
                case '97': { // Vehicle Accessories Endorsement 
                    $sequence = 23;
                    break;
                }
                case '97A': { // Gas Conversion Kit Tank
                    $sequence = 24;
                    break;
                }
                case 'D1': { // Demonstration
                    $sequence = 25;
                    break;
                }
                case 'TW1': { // Inclusion Of Third Party
                    $sequence = 26;
                    break;
                }
                case 'TW2': { // Inclusion Of Third Party Working Risk - All
                    $sequence = 27;
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
                case '01A': { // Authorised Driver
                    $sequence = 32;
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

    private function getStateCode(string $state)
    {
        $code = '';

        switch($state) {
            case 'Perlis':{
                $code = '01';
                break;
            }
            case 'Kedah':{
                $code = '02';
                break;
            }
            case 'Pulau Pinang': {
                $code = '03';
                break;
            }
            case 'Perak':{
                $code = '04';
                break;
            }
            case 'Selangor':{
                $code = '05';
                break;
            }
            case 'Wilayah Persekutuan Kuala Lumpur': {
                $code = '06';
                break;
            }
            case 'Johor':{
                $code = '07';
                break;
            }
            case 'Melaka':{
                $code = '08';
                break;
            }
            case 'Negeri Sembilan': {
                $code = '09';
                break;
            }
            case 'Pahang':{
                $code = '10';
                break;
            }
            case 'Terengganu': {
                $code = '11';
                break;
            }
            case 'Kelantan':{
                $code = '12';
                break;
            }
            case 'Sabah':{
                $code = '13';
                break;
            }
            case 'Sarawak':{
                $code = '14';
                break;
            }
            case 'Wilayah Persekutuan Labuan': {
                $code = '15';
                break;
            }
            case 'Wilayah Persekutuan Putrajaya': {
                $code = '16';
                break;
            }
        }
        return $code;
    }

    private function body_type_code(string $body_type)
    {
        $code = '';
        switch($body_type) {
            case 'FOUR WHEEL DRIVE':{
                $code = '1';
                break;
            }
            case 'AEROBACK':{
                $code = '2';
                break;
            }
            case 'AGRICULTURAL':{
                $code = '3';
                break;
            }
            case 'AMBULANCE':{
                $code = '4';
                break;
            }
            case 'ALUMINIUM WITH ALUMINIUM ROOF':{
                $code = '5';
                break;
            }
            case 'BULLDOZER':{
                $code = '6';
                break;
            }
            case 'BACKHOE LOADER':{
                $code = '7';
                break;
            }
            case 'BUS':{
                $code = '8';
                break;
            }
            case 'BOX VAN':{
                $code = '9';
                break;
            }
            case 'CREW/DCAB PICKUP':{
                $code = '10';
                break;
            }
            case 'CASE':{
                $code = '11';
                break;
            }
            case 'CABRIOLET':{
                $code = '12';
                break;
            }
            case 'C/CHAS':{
                $code = '13';
                break;
            }
            case 'CONCRETE MIXER':{
                $code = '14';
                break;
            }
            case 'COMEL':{
                $code = '15';
                break;
            }
            case 'COUPE':{
                $code = '16';
                break;
            }
            case 'COMPACTOR':{
                $code = '17';
                break;
            }
            case 'CARGO':{
                $code = '18';
                break;
            }
            case 'MOBILE CRANE':{
                $code = '19';
                break;
            }
            case 'CARAVAN':{
                $code = '20';
                break;
            }
            case 'CATERPILLAR':{
                $code = '21';
                break;
            }
            case '2D CONVERTIBLE':{
                $code = '22';
                break;
            }
            case 'CANVAS TOP':{
                $code = '23';
                break;
            }
            case 'MOTORCYCLE':{
                $code = '24';
                break;
            }
            case 'DUAL CAB PICKUP':{
                $code = '25';
                break;
            }
            case '4D DOUBLE CAB PICK UP':{
                $code = '26';
                break;
            }
            case '3D HATCHBACK':{
                $code = '27';
                break;
            }
            case 'DUMPER TIPPER':{
                $code = '28';
                break;
            }
            case 'EXCAVATOR':{
                $code = '29';
                break;
            }
            case '4D CONVERTIBLE':{
                $code = '30';
                break;
            }
            case '4D HATCHBACK':{
                $code = '31';
                break;
            }
            case '4D COUPE':{
                $code = '32';
                break;
            }
            case '4D 4D SEDAN':{
                $code = '33';
                break;
            }
            case '5D HATCHBACK':{
                $code = '34';
                break;
            }
            case '4D VAN':{
                $code = '35';
                break;
            }
            case '4D 4D WAGON':{
                $code = '36';
                break;
            }
            case 'FORKLIFT':{
                $code = '37';
                break;
            }
            case 'FIRE BRIGADE':{
                $code = '38';
                break;
            }
            case 'GRAB LOADER':{
                $code = '39';
                break;
            }
            case 'HARD TOP':{
                $code = '40';
                break;
            }
            case 'HEARSES':{
                $code = '41';
                break;
            }
            case 'HATCHBACK':{
                $code = '42';
                break;
            }
            case 'HYDRAULIC':{
                $code = '43';
                break;
            }
            case 'JEEP':{
                $code = '44';
                break;
            }
            case 'LIFTBACK':{
                $code = '45';
                break;
            }
            case 'LIMOUSINE':{
                $code = '46';
                break;
            }
            case 'LORRY':{
                $code = '47';
                break;
            }
            case 'LUTON VAN':{
                $code = '48';
                break;
            }
            case 'LORRY WITH CRANE':{
                $code = '49';
                break;
            }
            case 'MOBILE AERIAL PLATFORM':{
                $code = '50';
                break;
            }
            case 'MOBILE SHOPS AND CANTEENS':{
                $code = '51';
                break;
            }
            case 'MOBILE PLANT':{
                $code = '52';
                break;
            }
            case 'MOBILE CONCRETE PUMP':{
                $code = '53';
                break;
            }
            case 'MPV':{
                $code = '54';
                break;
            }
            case 'MOTORCYCLE SIDE CAR':{
                $code = '55';
                break;
            }
            case 'MOTOR GRADER':{
                $code = '56';
                break;
            }
            case 'MOTOR TRADE':{
                $code = '57';
                break;
            }
            case 'JEEP':{
                $code = '58';
                break;
            }
            case 'NAKED':{
                $code = '59';
                break;
            }
            case 'PICK-UP':{
                $code = '60';
                break;
            }
            case 'PANEL VAN':{
                $code = '61';
                break;
            }
            case 'PRIME MOVER':{
                $code = '62';
                break;
            }
            case 'REFRIGERATOR':{
                $code = '63';
                break;
            }
            case 'REFRIGERATED BOX':{
                $code = '64';
                break;
            }
            case 'ROAD PAVER':{
                $code = '65';
                break;
            }
            case 'ROAD ROLLER':{
                $code = '66';
                break;
            }
            case 'ROAD SWEEPER':{
                $code = '67';
                break;
            }
            case 'STEEL WITH ALUMINIUM ROOF':{
                $code = '68';
                break;
            }
            case '4D SINGLE CAB PICK-UP':{
                $code = '69';
                break;
            }
            case 'MOTORCYCLE SIDE-CAR':{
                $code = '70';
                break;
            }
            case 'SEDAN':{
                $code = '71';
                break;
            }
            case 'SOFTTOP':{
                $code = '72';
                break;
            }
            case '2D SINGLE CAB PICK UP':{
                $code = '73';
                break;
            }
            case 'SKY MASTER / SKYLIFT':{
                $code = '74';
                break;
            }
            case 'SALOON':{
                $code = '75';
                break;
            }
            case 'SEMI TRAILER LOW LOADER':{
                $code = '76';
                break;
            }
            case 'SPORT':{
                $code = '77';
                break;
            }
            case 'SEMI PANEL VAN':{
                $code = '78';
                break;
            }
            case 'STEEL TIPPER':{
                $code = '79';
                break;
            }
            case 'STEEL TRAY':{
                $code = '80';
                break;
            }
            case 'STATIONWAGON':{
                $code = '81';
                break;
            }
            case 'SPORT UTILITY VEHICLE':{
                $code = '82';
                break;
            }
            case 'SHOVEL':{
                $code = '83';
                break;
            }
            case 'STATIONWAGON':{
                $code = '84';
                break;
            }
            case 'TAXI':{
                $code = '85';
                break;
            }
            case '2D ROADSTER':{
                $code = '86';
                break;
            }
            case '3D VAN':{
                $code = '87';
                break;
            }
            case 'TANKER':{
                $code = '88';
                break;
            }
            case 'TRUCK':{
                $code = '89';
                break;
            }
            case 'TRAILER':{
                $code = '90';
                break;
            }
            case 'TRACTOR':{
                $code = '91';
                break;
            }
            case 'TOWING TRUCK':{
                $code = '92';
                break;
            }
            case 'VAN':{
                $code = '93';
                break;
            }
            case 'WOODEN CARGO':{
                $code = '94';
                break;
            }
            case 'WOODEN TIPPER':{
                $code = '95';
                break;
            }
            case 'WOOD TRAY':{
                $code = '96';
                break;
            }
            case 'WINDOW VAN':{
                $code = '97';
                break;
            }
            case 'WAGON':{
                $code = '98';
                break;
            }
            case 'WHEEL / CRAWLER':{
                $code = '99';
                break;
            }
            case 'WOOD WITH ALUMINIUM ROOF':{
                $code = '100';
                break;
            }
        }
        return $code;
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

    private function getVIXNCD(array $input) : ResponseData
    {
        $path = 'GetVehicleInfo';
        $request_datetime = Carbon::now()->format('Y-M-d h:i:s A');
        $dobs = str_split($input['id_number'], 2);
        $id = $dobs[0] . $dobs[1] . $dobs[2] . "-" . $dobs[3] .  "-" . $dobs[4] . $dobs[5];
        $VehNo = $input['vehicle_number'];
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
            'ISMNCDRespCode' => (string) $result_data->ISMNCDRespCode,
            'ISMVIXRespCode' => (string) $result_data->ISMVIXRespCode,
            'NxtPolEffDate' => (string) $result_data->NxtPolEffDate,
            'NxtPolExpDate' => (string) $result_data->NxtPolExpDate,
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