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
            'code' => 'string|nullable',
            'motor' => 'array|required'
        ]);

        if($validator->fails()) {
            return $this->abort($validator->errors());
        }

        $motor = toObject($request->motor);

        // 1. Find the code
        if(!empty($request->code)) {
            $code = Promotion::where('code', strtoupper($request->code))
                ->first();
        } else if($request->isAutoRoadTax) {
            $codes = Promotion::where('discount_target', Promotion::DT_ROADTAX)
                ->where('valid_from', '<=', Carbon::now()->format('Y-m-d H:i:s'))
                ->where('valid_to', '>=', Carbon::now()->format('Y-m-d H:i:s'))
                ->get();

            if(empty($codes)) {
                return;
            }

            $values = collect([]);

            foreach($codes as $promo) {
                $discount_amount = 0;

                if($promo->discount_percentage > 0 && $promo->discount_amount === 0) {
                    $discount_amount = $motor->premium->roadtax * ($promo->discount_percentage / 100);
                } else {
                    $discount_amount = $promo->discount_amount;
                }

                $values->push([
                    'code' => $promo->code,
                    'amount' => $discount_amount
                ]);
            }

            $highest_discount_code = $values->firstWhere('amount', $values->max('amount'));

            $code = $codes->filter(function($_code) use($highest_discount_code) {
                return $_code->code === $highest_discount_code['code'];
            })->first();
        }

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
                    $discount_amount = $motor->premium->{$code->discount_target} * ($code->discount_percentage / 100);
                } else {
                    $discount_amount = $code->discount_amount;
                }

                break;
            }
            case 'service_tax': {
                if($is_percentage) {
                    $discount_amount = $motor->premium->sst_amount * ($code->discount_percentage / 100);
                } else {
                    $discount_amount = $code->discount_amount;
                }

                break;
            }
            case 'road_tax': {
                if($is_percentage) {
                    $discount_amount = $motor->roadtax->total * ($code->discount_percentage / 100);
                } else {
                    if(floatval($code->discount_amount) > $motor->roadtax->total) {
                        $discount_amount = $motor->roadtax->total;
                    } else {
                        $discount_amount = $code->discount_amount;
                    }
                }

                break;
            }
            default: {
                return $this->abort(__('api.promo_discount_target_not_found', ['code' => $request->code]));
            }
        }

        /// Update Total Payable Amount
        $motor->premium->total_payable -= $discount_amount;

        try {
            DB::beginTransaction();

            if(!empty($motor->insurance_code)) {
                // 4a. Add Use Count
                Promotion::where('code', $request->code)
                    ->update(['use_count' => $code->use_count++]);

                // 4b. Update to InsurancePromo table
                $insurance = Insurance::where('insurance_code', $motor->insurance_code)
                    ->firstOrFail();

                InsurancePromo::where('insurance_id', $insurance->id)
                    ->delete();

                InsurancePromo::create([
                    'insurance_id' => $insurance->id,
                    'promo_id' => $code->id,
                    'discount_amount' => $discount_amount,
                ]);
            }

            $motor->promo = $code;
            $motor->premium->discounted_amount = floatval($discount_amount);
            Log::info("[API/Promo] Received Request: " . json_encode($motor));
            DB::commit();
            return $motor;
        } catch (Exception $ex) {
            Log::error("[API/UsePromoCode] An Error Encountered. [{$ex->getMessage()}] \n" . $ex);
            DB::rollback();

            return $this->abort("An Error Encountered. {$ex->getMessage()}");
        }
    }
}
