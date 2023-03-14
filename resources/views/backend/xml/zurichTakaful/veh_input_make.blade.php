<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetVehicleMake xmlns="https://api.zurich.com.my/v1/takaful/insurance/motor">
            <MakeInputInfo>{{ $data }}</MakeInputInfo>
        </GetVehicleMake>
    </s:Body>
</s:Envelope>