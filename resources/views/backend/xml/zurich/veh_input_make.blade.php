<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetVehicleMake xmlns="https://gtws2.zurich.com.my/ziapps/zurichinsurance">
            <MakeInputInfo>{{ $data }}</MakeInputInfo>
        </GetVehicleMake>
    </s:Body>
</s:Envelope>