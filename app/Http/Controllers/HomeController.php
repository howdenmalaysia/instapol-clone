<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
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
}
