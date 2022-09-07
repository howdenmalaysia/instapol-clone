<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <RequestVehicleInfo xmlns="http://tempuri.org/">
            <!-- Optional -->
            <reqData>
                <TokenId>{{ $token }}</TokenId>
                <NRIC>{{ $ic_number }}</NRIC>
                <VehicleRegNo>{{ $vehicle_number }}</VehicleRegNo>
            </reqData>
        </RequestVehicleInfo>
    </Body>
</Envelope>