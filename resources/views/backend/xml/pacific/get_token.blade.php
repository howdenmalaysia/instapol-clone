<s11:Envelope xmlns:s11='http://schemas.xmlsoap.org/soap/envelope/'>
  <s11:Body>
    <ns1:GetAccessToken xmlns:ns1='http://tempuri.org/'>
      <ns1:reqData>
        <ns2:agentId xmlns:ns2='http://schemas.datacontract.org/2004/07/PO.TravelAssurance'>{{ $agent_code }}</ns2:agentId>
        <ns2:productId xmlns:ns2='http://schemas.datacontract.org/2004/07/PO.TravelAssurance'>{{ $product }}</ns2:productId>
        <ns2:refNo xmlns:ns2='http://schemas.datacontract.org/2004/07/PO.TravelAssurance'>{{ $ref_no }}</ns2:refNo>
        <ns2:userId xmlns:ns2='http://schemas.datacontract.org/2004/07/PO.TravelAssurance'>{{ $user_id }}</ns2:userId>
      </ns1:reqData>
    </ns1:GetAccessToken>
  </s11:Body>
</s11:Envelope>