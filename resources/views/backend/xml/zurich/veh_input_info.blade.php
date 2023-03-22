<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetVehicleInfo xmlns="https://gtws2.zurich.com.my/ziapps/zurichinsurance">
            <VehInputInfo>{{ $data }}</VehInputInfo>
        </GetVehicleInfo>
    </s:Body>
</s:Envelope>