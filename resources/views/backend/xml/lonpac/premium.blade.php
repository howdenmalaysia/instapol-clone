<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ws="http://ws">
   <soapenv:Header/>
   <soapenv:Body>
      <ws:getInpuobj>
         <ws:TransTimestamp>{{ $timestamp }}</ws:TransTimestamp>
         <ws:HashCode>{{ $hashcode }}</ws:HashCode>
         <ws:Code>P</ws:Code>
         <ws:CompanyCode>{{ $company_code }}</ws:CompanyCode>
         <ws:BranchCode></ws:BranchCode>
         <ws:AgentCode>{{ $agent_code }}</ws:AgentCode>
         <ws:UserId>{{ $user_id }}</ws:UserId>
         <ws:CoverNoteNo></ws:CoverNoteNo>
         <ws:QuotationNo>{{ $quotation_number }}</ws:QuotationNo>
         <ws:SalesmanId></ws:SalesmanId>
         <ws:CoverageCode>CP</ws:CoverageCode>
         <ws:EffectiveDate>{{ $effective_date }}</ws:EffectiveDate>
         <ws:EffectiveTime>{{ $effective_time }}</ws:EffectiveTime>
         <ws:ExpiryDate>{{ $expiry_date }}</ws:ExpiryDate>
         <ws:IssuanceTimestamp></ws:IssuanceTimestamp>
         <ws:PrevPolicyNumber></ws:PrevPolicyNumber>
         <ws:PrevInsurerCode>{{ $pre_ins_code }}</ws:PrevInsurerCode>
         <ws:PrevInsurerName></ws:PrevInsurerName>
         <ws:PrevVehicleNo></ws:PrevVehicleNo>
         <ws:PrevNCDPerc></ws:PrevNCDPerc>
         <ws:HirePurchaseCode></ws:HirePurchaseCode>
         <ws:HirePurchaseName></ws:HirePurchaseName>
         <ws:N4NIndicator></ws:N4NIndicator>
         <ws:CustomerConsent></ws:CustomerConsent>
         <ws:Name>{{ $name }}</ws:Name>
         <ws:Email>{{ $email }}</ws:Email>
         <ws:IcNumber>{{ $id_number }}</ws:IcNumber>
         <ws:IcNoOld></ws:IcNoOld>
         <ws:BizRegNo></ws:BizRegNo>
         <ws:DateOfBirth>{{ $date_of_birth }}</ws:DateOfBirth>
         <ws:Age>{{ $age }}</ws:Age>
         <ws:Gender>{{ $gender }}</ws:Gender>
         <ws:MaritalStatus>{{ $marital_status }}</ws:MaritalStatus>
         <ws:Occupation>{{ $occupation }}</ws:Occupation>
         <ws:OccupationCode>{{ $occupation_code }}</ws:OccupationCode>
         <ws:DrivingExperience></ws:DrivingExperience>
         <ws:OccupEmployStat></ws:OccupEmployStat>
         <ws:IDType></ws:IDType>
         <ws:AddLine1>{{ $address_1 }}</ws:AddLine1>
         <ws:AddLine2>{{ $address_2 }}</ws:AddLine2>
         <ws:AddLine3>{{ $address_3 }}</ws:AddLine3>
         <ws:CityTown>{{ $region }}</ws:CityTown>
         <ws:Postcode>{{ $postcode }}</ws:Postcode>
         <ws:Mobile>{{ $phone_number }}</ws:Mobile>
         <ws:House></ws:House>
         <ws:Office></ws:Office>
         <ws:LogBookNo></ws:LogBookNo>
         <ws:TrailerNo></ws:TrailerNo>
         <ws:RegistrationNo>{{ $vehicle_number }}</ws:RegistrationNo>
         <ws:ChassisNo>{{ $chassis_number }}</ws:ChassisNo>
         <ws:EngineNo>{{ $engine_number }}</ws:EngineNo>
         <ws:MakeCode>{{ $make }}</ws:MakeCode>
         <ws:ModelCode>{{ $model }}</ws:ModelCode>
         <ws:MakeModelDesc>{{ $makemodeldesc }}</ws:MakeModelDesc>
         <ws:YearManufactured>{{ $manufacture_year }}</ws:YearManufactured>
         <ws:EngineCapacity>{{ $vehicle_capacity }}</ws:EngineCapacity>
         <ws:EngineCapacityCode>{{ $vehicle_capacity_code }}</ws:EngineCapacityCode>
         <ws:SeatingCapacity>{{ $seat_capacity }}</ws:SeatingCapacity>
         <ws:BodyType>001</ws:BodyType>
         <ws:VehicleAge>{{ $vehicle_age }}</ws:VehicleAge>
         <ws:VehicleClass></ws:VehicleClass>
         <ws:VehicleClassCode>02</ws:VehicleClassCode>
         <ws:VehicleType></ws:VehicleType>
         <ws:VehicleTypeCode>VP02</ws:VehicleTypeCode>
         <ws:VehicleUse></ws:VehicleUse>
         <ws:VehicleUseCode></ws:VehicleUseCode>
         <ws:OwnershipType>{{ $ownership_type }}</ws:OwnershipType>
         <ws:Condition></ws:Condition>
         <ws:MotorcycleRider></ws:MotorcycleRider>
         <ws:Garage>{{ $garage_code }}</ws:Garage>
         <ws:AuthorisedDriver></ws:AuthorisedDriver>
         <ws:SafetyFeature>{{ $safety_code }}</ws:SafetyFeature>
         <ws:AntiTheft>{{ $anti_theft }}</ws:AntiTheft>
         <ws:VehicleClaim></ws:VehicleClaim>
         <ws:WindscreenClaim></ws:WindscreenClaim>
         <ws:TheftClaim></ws:TheftClaim>
         <ws:ThirdPartyClaim></ws:ThirdPartyClaim>
         <ws:ClaimAmount></ws:ClaimAmount>
         <ws:LocalImportInd>{{ $localimport }}</ws:LocalImportInd>
         <ws:ModificationCode></ws:ModificationCode>
         <ws:FuncModification></ws:FuncModification>
         <ws:NVIC>{{ $nvic }}</ws:NVIC>
         <ws:TraffViolationCode></ws:TraffViolationCode>
         <ws:DriverIndex></ws:DriverIndex>
         <ws:DriverName></ws:DriverName>
         <ws:DriverIcNumber></ws:DriverIcNumber>
         <ws:DriverPassport></ws:DriverPassport>
         <ws:DriverDateOfBirth></ws:DriverDateOfBirth>
         <ws:DriverOccupation></ws:DriverOccupation>
         <ws:DriverOccupationCode></ws:DriverOccupationCode>
         <ws:DriverAge></ws:DriverAge>
         <ws:DriverDrivingExperience></ws:DriverDrivingExperience>
         <ws:DriverGender></ws:DriverGender>
         <ws:DriverRelationship></ws:DriverRelationship>
         <ws:AddCovDesc>{{ $addcovdesc }}</ws:AddCovDesc>
         <ws:AddCovCode>{{ $addcovcode }}</ws:AddCovCode>
         <ws:AddCovSI>{{ $addcovsi}}</ws:AddCovSI>
         <ws:AddCovUnit>{{ $addcovunit }}</ws:AddCovUnit>
         <ws:AddCovPrem></ws:AddCovPrem>
         <ws:SumInsured>{{ $sum_insured }}</ws:SumInsured>
         <ws:ActPremium></ws:ActPremium>
         <ws:Excess></ws:Excess>
         <ws:TariffPremium></ws:TariffPremium>
         <ws:AllRiderLoading></ws:AllRiderLoading>
         <ws:LoadingPercent></ws:LoadingPercent>
         <ws:LoadingAmount></ws:LoadingAmount>
         <ws:NCDRecoveryAmount></ws:NCDRecoveryAmount>
         <ws:NCDPercent>{{ $ncd_percentage }}</ws:NCDPercent>
         <ws:NCDAmount></ws:NCDAmount>
         <ws:StampDuty></ws:StampDuty>
         <ws:TransferFee></ws:TransferFee>
         <ws:CommissionPercent></ws:CommissionPercent>
         <ws:CommissionAmount></ws:CommissionAmount>
         <ws:ServiceTaxPercent></ws:ServiceTaxPercent>
         <ws:ServiceTaxAmount></ws:ServiceTaxAmount>
         <ws:GrossPremium></ws:GrossPremium>
         <ws:NetPremium></ws:NetPremium>
         <ws:GrossDue></ws:GrossDue>
         <ws:NetDue></ws:NetDue>
         <ws:RoundedGrossDue></ws:RoundedGrossDue>
         <ws:RoundedNetDue></ws:RoundedNetDue>
         <ws:OldPolNo></ws:OldPolNo>
         <ws:GSTPurpose></ws:GSTPurpose>
         <ws:GSTSoleProprietor></ws:GSTSoleProprietor>
         <ws:GSTRegNo></ws:GSTRegNo>
         <ws:GSTRegDate></ws:GSTRegDate>
         <ws:GSTCode>{{ $gst_code }}</ws:GSTCode>
         <ws:GSTPerc></ws:GSTPerc>
         <ws:GSTAmount></ws:GSTAmount>
         <ws:GSTAmtComm></ws:GSTAmtComm>
         <ws:TaxInvNo></ws:TaxInvNo>
         <ws:TaxEffDate></ws:TaxEffDate>
         <ws:AgreedValInd>Y</ws:AgreedValInd>
         <ws:ClaimFreeYear></ws:ClaimFreeYear>
         <ws:SDPAInd></ws:SDPAInd>
         <ws:ncdRespCode>000</ws:ncdRespCode>
         <ws:ncdRespMsg>ENQ-OK for successful NCD confirmation/Enquiry</ws:ncdRespMsg>
         <ws:QuotationStatus>C</ws:QuotationStatus>
         <ws:modelDesc></ws:modelDesc>
         <ws:rebatePercent>0.00</ws:rebatePercent>
         <ws:paRebatePercent>0.00</ws:paRebatePercent>
         <ws:custDiscPercent>0.00</ws:custDiscPercent>
         <ws:paCustDiscPercent>0.00</ws:paCustDiscPercent>
      </ws:getInpuobj>
   </soapenv:Body>
</soapenv:Envelope>