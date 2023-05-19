<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:getInputData>
         <ws:TransTimestamp>{{ $timestamp }}</ws:TransTimestamp>
         <ws:HashCode>{{ $hash_code }}</ws:HashCode>
         <ws:AgentCode>{{ $agent_code }}</ws:AgentCode>
         <ws:CoverNoteNo>{{ $covernote }}</ws:CoverNoteNo>
         <ws:LPIPaymentInd>N</ws:LPIPaymentInd>
      </ws:getInputData>
   </soapenv:Body>
</soapenv:Envelope>