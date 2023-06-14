<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:getInpuobj>
         <ws:vehRegNo>{{ $vehicle_number }}</ws:vehRegNo>
         <ws:agentCode>{{ $agent_code }}</ws:agentCode>
         <ws:newIcNo>{{ $id_number }}</ws:newIcNo>
         <ws:oldIcNo></ws:oldIcNo>
         <ws:busRegNo>{{ $company_registration_number }}</ws:busRegNo>
         <ws:stateCode>{{ $state_code }}</ws:stateCode>
         <ws:reqParam3>{{ $post_code }}</ws:reqParam3>
         <ws:userID>{{ $user_id }}</ws:userID>
         <ws:timeStamp>{{ $timestamp }}</ws:timeStamp>
         <ws:companyCode>{{ $company_code }}</ws:companyCode>
         <ws:hashCode>{{ $hash_code }}</ws:hashCode>
         <ws:classCode>{{ $class_code }}</ws:classCode>
         <ws:coverType>{{ $cover_type }}</ws:coverType>
      </ws:getInpuobj>
   </soapenv:Body>
</soapenv:Envelope>
