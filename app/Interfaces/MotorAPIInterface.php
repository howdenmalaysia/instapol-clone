<?php

use Illuminate\Http\Request;

interface MotorAPIInterface {
    public function getVehicleDetails(Request $request);
    public function getQuote(Request $request, $quote_type);
    public function createQuotation(Request $request);
    public function submitCoverNote(Request $request);
}