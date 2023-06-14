<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:getInputData>
         <ws:TransTimestamp>{{ $timestamp }}</ws:TransTimestamp>
         <ws:HashCode>{{ $hash_code }}</ws:HashCode>
         <ws:AgentCode>{{ $agent_code }}</ws:AgentCode>
         <ws:RegistrationNo>{{ $vehicle_number }}</ws:RegistrationNo>
         <ws:PDPATimestamp>{{ $pdpatimestamp }}</ws:PDPATimestamp>
         <ws:PDPAConsent>Y</ws:PDPAConsent>
         <ws:PDSTimestamp>{{ $pdpatimestamp }}</ws:PDSTimestamp>
         <ws:MarketingInd></ws:MarketingInd>
         <ws:QuotationNo>{{ $quotationNo }}</ws:QuotationNo>
         <ws:PaymentStatus>C</ws:PaymentStatus>
         <ws:LPIPaymentInd>N</ws:LPIPaymentInd>
         <ws:TotDue>{{ $totdue }}</ws:TotDue>
         <ws:AddCovCode>{{ $addcovcode }}</ws:AddCovCode>
         <ws:PaymentAmt></ws:PaymentAmt>
         <ws:PaymentDate></ws:PaymentDate>
         <ws:PaymentType></ws:PaymentType>
         <ws:CcNo></ws:CcNo>
         <ws:CcName></ws:CcName>
         <ws:CcBrand></ws:CcBrand>
         <ws:RoadTaxPrem></ws:RoadTaxPrem>
         <ws:RoadTaxFee></ws:RoadTaxFee>
         <ws:RoadTaxServiceChrg></ws:RoadTaxServiceChrg>
         <ws:RoadTaxSSTAmt></ws:RoadTaxSSTAmt>
         <ws:RoadTaxSSTRate></ws:RoadTaxSSTRate>
         <ws:RoadTaxGrossDue></ws:RoadTaxGrossDue>
      </ws:getInputData>
   </soapenv:Body>
</soapenv:Envelope>