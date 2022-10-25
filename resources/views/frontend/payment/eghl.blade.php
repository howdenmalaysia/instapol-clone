<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title>Payment eGHL</title>
        <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <script src="https://code.jquery.com/jquery-3.5.1.min.js"
            integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
    </head>

    <body>
        <form method="POST" action="{{ config('setting.payment.eghl.payment_url') }}" id="eghl-form">
            <input type="hidden" name="TransactionType" value="{{ $transaction_type }}">
            <input type="hidden" name="PymtMethod" value="{{ strtolower(session('referral_code')) === 'mcash' ? Str::replaceFirst('CC|', '', $payment_method) : $payment_method }}">
            <input type="hidden" name="ServiceID" value="{{ config('setting.payment.eghl.merchant_id') }}">
            <input type="hidden" name="PaymentID" value="{{ $payment_id }}">
            <input type="hidden" name="OrderNumber" value="{{ $order_number }}">
            <input type="hidden" name="PaymentDesc" value="{{ $payment_description }}">
            <input type="hidden" name="MerchantName" value="{{ config('setting.payment.eghl.merchant_name') }}">
            <input type="hidden" name="MerchantReturnURL" value="{{ $return_url }}">
            <input type="hidden" name="MerchantCallbackURL" value="{{ $callback_url }}">
            <input type="hidden" name="Amount" value="{{ $amount }}">
            <input type="hidden" name="CurrencyCode" value="{{ $currency }}">
            <input type="hidden" name="CustIP" value="{{ $ip }}">
            <input type="hidden" name="CustName" value="{{ $customer_name }}">
            <input type="hidden" name="CustEmail" value="{{ $customer_email }}">
            <input type="hidden" name="CustPhone" value="{{ $customer_phone_number }}">
            <input type="hidden" name="HashValue" value="{{ $hash }}">
            <input type="hidden" name="MerchantTermsURL" value="{{ url(config('setting.motor_url').'/terms-and-conditions') }}">
            <input type="hidden" name="LanguageCode" value="{{ $language }}">
            <input type="hidden" name="PageTimeout" value="{{ $timeout }}">
            <input type="hidden" name="IssuingBank" value="{{ strtolower(session('referral_code')) === 'mcash' ? 'MCash' : '' }}">
        </form>
    </body>

    <script>
        $(() => {
            $('#eghl-form').submit();
        });
    </script>
</html>
