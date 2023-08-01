<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $referrer = $request->get('r');
        $request->session()->put('referrer', $referrer);

        $previous_insurance = $request->get('param');
        $request->session()->put('previous_insurance', $previous_insurance);
        
        return view('frontend.index');
    }

    public function aboutUs()
    {
        return view('frontend.about_us');
    }

    public function privacyPolicy()
    {
        return view('frontend.privacy_policy');
    }

    public function cookiePolicy()
    {
        return view('frontend.cookie_policy');
    }

    public function refundPolicy()
    {
        return view('frontend.refund_policy');
    }

    public function termsAndConditions()
    {
        return view('frontend.terms_and_conditions');
    }

    public function claims()
    {
        return view('frontend.claims');
    }
    public function UnderMaintenance()
    {
        return view('frontend.UnderMaintenance');
    }
    public function UnderMaintenance2()
    {
        return view('frontend.UnderMaintenance');
    }
    public function faq()
    {
        return view('frontend.faq');
    }
}
