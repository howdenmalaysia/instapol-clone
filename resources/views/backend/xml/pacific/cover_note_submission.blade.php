<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <PolicySubmission xmlns="http://tempuri.org/">
            <reqData xmlns:d4p1="http://schemas.datacontract.org/2004/07/PO.Web.API" xmlns:i="http://www.w3.org/2001/XMLSchema-instance" i:type="d4p1:motorSubmissionReq">
                <d4p1:lastRequestId xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $request_id }}</d4p1:lastRequestId>
                <d4p1:refNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $reference_number }}</d4p1:refNo>
                <d4p1:tokenId xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $token }}</d4p1:tokenId>
                <d4p1:ActPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->act_premium }}</d4p1:ActPremium>
                <d4p1:Address1 xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $address_one }}</d4p1:Address1>
                <d4p1:Address2 xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $address_two }}</d4p1:Address2>
                <d4p1:Address3 xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:Address3>
                <d4p1:Address4 xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:Address4>
                <d4p1:Address5 xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:Address5>
                <d4p1:AntiTheftDevice xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $anti_theft }}</d4p1:AntiTheftDevice>
                <d4p1:BasicNettPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->basic_nett_premium }}</d4p1:BasicNettPremium>
                <d4p1:BasicPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->basic_premium }}</d4p1:BasicPremium>
                <d4p1:ChassisNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $chassis_number }}</d4p1:ChassisNo>
                <d4p1:City xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $city }}</d4p1:City>
                <d4p1:CommissionAmount xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $commission_amount }}</d4p1:CommissionAmount>
                <d4p1:CommissionRate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $commission_rate }}</d4p1:CommissionRate>
                <d4p1:CountryCode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $country_code }}</d4p1:CountryCode>
                <d4p1:CoverNoteDate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $cover_note_date }}</d4p1:CoverNoteDate>
                <d4p1:CoverNoteEffectiveDate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $cover_note_effective_date }}</d4p1:CoverNoteEffectiveDate>
                <d4p1:CoverNoteExpiredDate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $cover_note_expiry_date }}</d4p1:CoverNoteExpiredDate>
                <d4p1:CoverNoteNumber xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:CoverNoteNumber>
                <d4p1:CoverRegionCode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $cover_region_code }}</d4p1:CoverRegionCode>
                <d4p1:CustomerCategory xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $customer_category }}</d4p1:CustomerCategory>
                <d4p1:CustomerName xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $customer_name }}</d4p1:CustomerName>
                <d4p1:CustomerNewICNumber xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $id_number }}</d4p1:CustomerNewICNumber>
                <d4p1:CustomerOldICNumber xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:CustomerOldICNumber>
                <d4p1:DOB xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $date_of_birth }}</d4p1:DOB>
                <d4p1:DetariffIndicator xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->detariff }}</d4p1:DetariffIndicator>
                <d4p1:DetariffPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->detariff_premium }}</d4p1:DetariffPremium>
                <d4p1:DiscountAmount xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->discount }}</d4p1:DiscountAmount>
                <d4p1:DiscountPercent xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->discount_amount }}</d4p1:DiscountPercent>
                <d4p1:EmailAddress xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $email }}</d4p1:EmailAddress>
                <d4p1:EmailAggregator xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $email_aggregator }}</d4p1:EmailAggregator>
                <d4p1:EngineNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $engine_number }}</d4p1:EngineNo>
                <d4p1:ExcessAmount xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->excess_amount }}</d4p1:ExcessAmount>
                
                @if (!empty($extra_coverage))
                    <d4p1:ExtraCoverage xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">
                        @foreach ($extra_coverage as $extra)
                            <extraCoverageSubmissionReq>
                                <coverageId>{{ $extra->extra_cover_code }}</coverageId>
                                <premium>{{ $extra->premium }}</premium>
                                <sumInsured>{{ $extra->sum_insured }}</sumInsured>
                            </extraCoverageSubmissionReq>
                        @endforeach
                    </d4p1:ExtraCoverage>
                @endif

                <d4p1:FinanceCompany xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:FinanceCompany>
                <d4p1:GSTAmountOnCommission xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:GSTAmountOnCommission>
                <d4p1:GSTAmountOnGrossPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:GSTAmountOnGrossPremium>
                <d4p1:GSTPercentageOnCommission xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:GSTPercentageOnCommission>
                <d4p1:GSTPercentageOnGrossPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:GSTPercentageOnGrossPremium>
                <d4p1:GSTRegistrationNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:GSTRegistrationNo>
                <d4p1:GarageCode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $garage_code }}</d4p1:GarageCode>
                <d4p1:Gender xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $gender }}</d4p1:Gender>
                <d4p1:GrossPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->gross_premium }}</d4p1:GrossPremium>
                <d4p1:ImportType xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $import_type }}</d4p1:ImportType>
                <d4p1:LoadAmount xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->loading_amount }}</d4p1:LoadAmount>
                <d4p1:LogBookNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $logbook_number }}</d4p1:LogBookNo>
                <d4p1:MaritalStatus xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $marital_status }}</d4p1:MaritalStatus>
                <d4p1:MobileTelephoneNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $phone_number }}</d4p1:MobileTelephoneNo>
                <d4p1:NCDAmount xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $ncd_amount }}</d4p1:NCDAmount>
                <d4p1:NCDRate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $ncd_percentage }}</d4p1:NCDRate>
                <d4p1:NVIC xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $nvic }}</d4p1:NVIC>
                <d4p1:NamedDriver xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">

                    <namedDriverSubmissionReq>
                        <age>{{ $age }}</age>
                        <gender>{{ $gender }}</gender>
                        <name>{{ $insured_name }}</name>
                        <nricNo>{{ $id_number }}</nricNo>
                        <relation>0</relation>
                    </namedDriverSubmissionReq>
                    @if (!empty($additional_driver))
                        @foreach ($additional_driver as $driver)
                            <namedDriverSubmissionReq>
                                <age>{{ $driver->age }}</age>
                                <gender>{{ $driver->gender }}</gender>
                                <name>{{ $driver->name }}</name>
                                <nricNo>{{ $driver->id_number }}</nricNo>
                                <relation>{{ $driver->relationship }}</relation>
                            </namedDriverSubmissionReq>
                        @endforeach
                    @endif

                </d4p1:NamedDriver>
                <d4p1:NonActPrem xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->non_act_premium }}</d4p1:NonActPrem>
                <d4p1:Occupation xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $occupation }}</d4p1:Occupation>
                <d4p1:PermittedDrivers xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $permitted_drivers }}</d4p1:PermittedDrivers>
                <d4p1:PostCode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $postcode }}</d4p1:PostCode>
                <d4p1:PremiumBeforeRebate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->premium_before_rebate }}</d4p1:PremiumBeforeRebate>
                <d4p1:PreviousInsurerRegNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:PreviousInsurerRegNo>
                <d4p1:Race xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></d4p1:Race>
                <d4p1:RebateAmount xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->rebate_amount }}</d4p1:RebateAmount>
                <d4p1:RegistrationNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $vehicle_number }}</d4p1:RegistrationNo>
                <d4p1:SafetyFeatureCode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $safety_feature_code }}</d4p1:SafetyFeatureCode>
                <d4p1:SeatCapacity xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $seat_capacity }}</d4p1:SeatCapacity>
                <d4p1:ServiceTax xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->sst_amount }}</d4p1:ServiceTax>
                <d4p1:StampDuty xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->stamp_duty }}</d4p1:StampDuty>
                <d4p1:StateCode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $state_code }}</d4p1:StateCode>
                <d4p1:SumInsured xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $sum_insured }}</d4p1:SumInsured>
                <d4p1:TariffPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $premium_details->tariff_premium }}</d4p1:TariffPremium>
                <d4p1:TotalPremium xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $total_premium }}</d4p1:TotalPremium>
                <d4p1:TypeOfCoverCode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $type_of_cover }}</d4p1:TypeOfCoverCode>
                <d4p1:YearMake xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $year_make }}</d4p1:YearMake>
            </reqData>
        </PolicySubmission>
    </Body>
</Envelope>