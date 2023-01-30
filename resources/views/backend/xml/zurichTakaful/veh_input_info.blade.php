<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetVehicleInfo xmlns="https://api.zurich.com.my/v1/takaful/insurance/motor">
            <VehInputInfo>{{ $data }}</VehInputInfo>
        </GetVehicleInfo>
    </s:Body>
</s:Envelope>