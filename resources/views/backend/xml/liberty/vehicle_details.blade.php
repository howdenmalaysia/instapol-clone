<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servlet" xmlns:urn="urn:GetVIXNCD" xmlns:get="https://d1.financial-link.com.my/AGS/services/GetVIXNCD">
   <soapenv:Header/>
   <soapenv:Body>
      <ser:getVixNcdReq>
         <urn:agentcode>{{ $agent_code }}</urn:agentcode>
         <urn:arrExtraParam></urn:arrExtraParam>
         <urn:bizregno>{{ $company_registration_number }}</urn:bizregno>
         <urn:compcode>{{ $company_code }}</urn:compcode>
         <urn:newic>{{ $ic_number }}</urn:newic>
         <urn:oldic></urn:oldic>
         <urn:passportno></urn:passportno>
         <urn:regno>{{ $vehicle_number }}</urn:regno>
         <urn:requestid>{{ $request_id }}</urn:requestid>
         <urn:signature>{{ $signature }}</urn:signature>
      </ser:getVixNcdReq>
   </soapenv:Body>
</soapenv:Envelope>
