<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetVehicleModel xmlns="https://gtws2.zurich.com.my/zurichtakaful">
            <ModelInputInfo>{{ $data }}</ModelInputInfo>
        </GetVehicleModel>
    </s:Body>
</s:Envelope>