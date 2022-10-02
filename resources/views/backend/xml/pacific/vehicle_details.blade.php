<s12:Envelope xmlns:s12='http://schemas.xmlsoap.org/soap/envelope/'>
  <s12:Body>
    <ns1:RequestVehicleInfo xmlns:ns1='http://tempuri.org/'>
      <ns1:reqData>
        <ns1:TokenId>{{ $token }}</ns1:TokenId>
        <ns1:NRIC>{{ $id_number }}</ns1:NRIC>
        <ns1:VehicleRegNo>{{ $vehicle_number }}</ns1:VehicleRegNo>
      </ns1:reqData>
    </ns1:RequestVehicleInfo>
  </s12:Body>
</s12:Envelope>