<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetVehicleModel xmlns="https://gtws2.zurich.com.my/ziapps/zurichinsurance">
            <ModelInputInfo>{{ $data }}</ModelInputInfo>
        </GetVehicleModel>
    </s:Body>
</s:Envelope>