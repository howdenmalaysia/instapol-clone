<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Motor\Insurance;
use App\Models\Motor\InsurancePromo;
use App\Models\Promotion;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PromoController extends Controller
{
    public function usePromoCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'string|required',
            'motor' => 'array|required'
        ]);

        if($validator->fails()) {
            return $this->abort($validator->errors());
        }

        $motor = toObject($request->motor);

        // 1. Find the code
        $code = Promotion::where('code', strtoupper($request->code))
            ->first();

        if(empty($code)) {
            return $this->abort(__('api.promo_code_not_found'));
        }

        // 2. Check the condition
        /// a. Validity
        if(Carbon::parse($code->valid_from)->greaterThan(Carbon::today())) {
            return $this->abort(__('api.promo_hasnt_start', ['start' => Carbon::parse($code->valid_from)->format('Y-m-d')]));
        }

        if(Carbon::parse($code->valid_to)->lessThan(Carbon::today())) {
            return $this->abort(__('api.promo_expired'));
        }

        /// b. Use Count
        if($code->use_count === $code->use_max && $code->use_max != 0) {
            return $this->abort(__('api.promo_code_ran_out'));
        }

        /// c. Min Spend
        if($code->minimum_spend != 0) {
            if($code->minimum_spend <= $motor->premium->total_payable) {
                return $this->abort(__('api.promo_min_spend_not_achieved', ['amount' => floatval($motor->premium->total_payable) - floatval($code->minimum_apend)]));
            }
        }


        /// d. Domain Restriction
        if($code->restrict_domain) {
            $allowed_domain = explode(', ', str_replace('.', '\.', $code->allowed_domain));
            
            if(!preg_match('/^(.*)@' . implode('|', $allowed_domain) . '/i', $motor->policy_holder->email)) {
                return $this->abort(__('api.promo_domain_not_allowed'));
            }
        }

        // 3. Manipulate Premium
        if($code->discount_amount <= 0 && $code->discount_percentage <= 0) {
            return $this->abort(__('api.promo_zero_discount'));
        }

        $is_percentage = $code->discount_percentage > 0 && $code->discount_amount === 0;
        $discount_amount = 0;

        switch($code->discount_target) {
            case 'basic_premium':
            case 'gross_premium':
            case 'stamp_duty':
            case 'total_payable': {
                if($is_percentage) {
                    $discount_amount = $motor->premium[$code->discount_target] * ($code->discount_percentage / 100);
                    $motor->premium[$code->discount_target] -= $discount_amount;
                } else {
                    $discount_amount = $code->discount_amount;
                    $motor->premium[$code->discount_target] -= $discount_amount;
                }

                break;
            }
            case 'service_tax': {
                if($is_percentage) {
                    $discount_amount = $motor->premium->sst_amount * ($code->discount_percentage / 100);
                    $motor->premium->sst_amount -= $discount_amount;
                } else {
                    $discount_amount = $code->discount_amount;
                    $motor->premium->sst_amount -= $discount_amount;
                }

                break;
            }
            case 'road_tax': {
                if($is_percentage) {
                    $discount_amount = $motor->roadtax->total * ($code->discount_percentage / 100);
                    $motor->roadtax->total -= $discount_amount;
                } else {
                    $discount_amount = $code->discount_amount;
                    $motor->roadtax->total -= $discount_amount;
                }

                break;
            }
            default: {
                return $this->abort(__('api.promo_discount_target_not_found', ['code' => $request->code]));
            }
        }

        try {
            DB::beginTransaction();

            // 4. Add Use Count
            Promotion::where('code', $request->code)
                ->update(['use_count' => $code->use_count++]);
    
            // 5. Update to InsurancePromo table
            $insurance = Insurance::where('insurance_code', $request->insurance_code)->firstOrFail();
            InsurancePromo::where('insurance_id', $insurance->id)->delete();
            InsurancePromo::create([
                'insurance_id' => $insurance->id,
                'promo_id' => $code->id,
                'discount_amount' => $discount_amount,
            ]);

            $motor->premium->discounted_amount = $discount_amount;

            DB::commit();
            return $motor;
        } catch (Exception $ex) {
            Log::error("[API/UsePromoCode] An Error Encountered. {$ex->getMessage()}");
            DB::rollback();

            return $this->abort("An Error Encountered. {$ex->getMessage()}");
        }
    }
}
