<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
  <s:Header>
    <Action s:mustUnderstand="1" xmlns="http://schemas.microsoft.com/ws/2005/05/addressing/none">{{ $soap_action }}</Action>
  </s:Header>
  <s:Body>
    <GetAccessToken xmlns="http://tempuri.org/">
      <reqData xmlns:d4p1="http://schemas.datacontract.org/2004/07/PO.TravelAssurance" xmlns:i="http://www.w3.org/2001/XMLSchema-instance">
        <d4p1:agentId>{{ $agent_code }}</d4p1:agentId>
        <d4p1:productId>{{ $product }}</d4p1:productId>
        <d4p1:refNo>{{ $ref_no }}</d4p1:refNo>
        <d4p1:userId>{{ $user_id }}</d4p1:userId>
      </reqData>
    </GetAccessToken>
  </s:Body>
</s:Envelope>
