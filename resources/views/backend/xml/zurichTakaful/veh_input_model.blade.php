<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetVehicleModel xmlns="https://api.zurich.com.my/v1/takaful/insurance/motor">
            <ModelInputInfo>{{ $data }}</ModelInputInfo>
        </GetVehicleModel>
    </s:Body>
</s:Envelope>