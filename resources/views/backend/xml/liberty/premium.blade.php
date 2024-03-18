<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://servlet"
    xmlns:urn="urn:GetPremium" xmlns:get="https://d1.financial-link.com.my/AGS/services/GetPremium"
    xmlns:mod="http://model">
    <soapenv:Header />
    <soapenv:Body>
        <ser:reqdata>
            <address1>{{ $address_1 }}</address1>
            <address2>{{ $address_2 }}</address2>
            <address3>{{ $address_3 }}</address3>
            <agentcode>{{ $agent_code }}</agentcode>
            <agtgstregdate />
            <agtgstregno />
            @if (count($extra_benefit) > 0)
                <addbendata>
                    @foreach ($extra_benefit as $_extra_benefit)
                        <item>
                            <bencode>{{ $_extra_benefit->extra_cover_code }}</bencode>
                            <bendesc>{{ $_extra_benefit->extra_cover_description }}</bendesc>
                            <benpremium>0</benpremium>
                            <cewcommperc>0</cewcommperc>
                            <cewstampduty>0</cewstampduty>
                            <suminsured>{{ $_extra_benefit->sum_insured }}</suminsured>
                            <unit>{{ $_extra_benefit->unit }}</unit>
                        </item>
                    @endforeach
                </addbendata>
            @endif
            @if (count($additional_driver) > 0)
                <adddrvdata xmlns="urn:GetPremium">
                    @foreach ($additional_driver as $index => $driver)
                        <item>
                            <drvage>{{ $driver->age }}</drvage>
                            <drvdob>{{ $driver->date_of_birth }}</drvdob>
                            <drvexp>{{ $driver->driving_exp }}</drvexp>
                            <drvgender>{{ $driver->gender }}</drvgender>
                            <drvmarital></drvmarital>
                            <drvoccup></drvoccup>
                            <drvrel></drvrel>
                            <icnumber>{{ $driver->id_number }}</icnumber>
                            <index>{{ $index + 1 }}</index>
                            <name>{{ $driver->name }}</name>
                            <oicnumber />
                        </item>
                    @endforeach
                </adddrvdata>
            @endif
            <urn:antitd>{{ $anti_theft }}</urn:antitd>
            <urn:birthdate>{{ $date_of_birth }}</urn:birthdate>
            <urn:bizregno>{{ $company_registration_number }}</urn:bizregno>
            <urn:channel />
            <urn:chassisno>{{ $chassis_number }}</urn:chassisno>
            <urn:claimamt>0</urn:claimamt>
            <urn:cncondition />
            <urn:commiperc>10</urn:commiperc>
            <urn:compcode>{{ $company_code }}</urn:compcode>
            <urn:country />
            <urn:covercode>{{ $cover_code }}</urn:covercode>
            <urn:discount />
            <urn:discountperc>0</urn:discountperc>
            <urn:driveexp>{{ $driving_experience }}</urn:driveexp>
            <urn:effectivedate>{{ $effective_date }}</urn:effectivedate>
            <urn:effectivetime>{{ $effective_time }}</urn:effectivetime>
            <urn:email>{{ $email }}</urn:email>
            <urn:engineno>{{ $engine_number }}</urn:engineno>
            <urn:expirydate>{{ $expiry_date }}</urn:expirydate>
            <urn:garage>{{ $garage_code }}</urn:garage>
            <urn:gender>{{ $gender }}</urn:gender>
            <urn:gstclaimperc>0</urn:gstclaimperc>
            <urn:gstcode>{{ $gst_code }}</urn:gstcode>
            <urn:gstpurpose />
            <urn:gstreg />
            <urn:gstregdate />
            <urn:gstregdateend />
            <urn:gstregno />
            <urn:hpcode></urn:hpcode>
            <urn:hphoneno>{{ $phone_number }}</urn:hphoneno>
            <urn:lessor></urn:lessor>
            <urn:loadingamt>0</urn:loadingamt>
            <urn:loadingperc>0</urn:loadingperc>
            <urn:makecodemajor>{{ $make_code }}</urn:makecodemajor>
            <urn:makecodeminor>{{ $liberty_model_code }}</urn:makecodeminor>
            <urn:makeyear>{{ $manufacture_year }}</urn:makeyear>
            <urn:maritalstatus>{{ $marital_status }}</urn:maritalstatus>
            <urn:mtcycrider />
            <urn:name>{{ $name }}</urn:name>
            <urn:ncdamt>0</urn:ncdamt>
            <urn:ncdperc>{{ $ncd_percentage }}</urn:ncdperc>
            <urn:newic>{{ $id_number }}</urn:newic>
            <urn:occupmajor>{{ $occupation }}</urn:occupmajor>
            <urn:oldic />
            <urn:ownershiptype>{{ $ownership_type }}</urn:ownershiptype>
            <urn:passportno />
            <urn:piamdrv>{{ $piam_driver }}</urn:piamdrv>
            <urn:postcode>{{ $postcode }}</urn:postcode>
            <urn:preinscode />
            <urn:preinsname />
            <urn:preinsncd>0</urn:preinsncd>
            <urn:preinspolno />
            <urn:preinsregno>{{ $vehicle_number }}</urn:preinsregno>
            <urn:prepoleffdate />
            <urn:prepolexpdate />
            <urn:purchasedate />
            <urn:purchaseprice>0</urn:purchaseprice>
            <urn:purpose>{{ $purpose }}</urn:purpose>
            <urn:quoteno>{{ $quotation_number }}</urn:quoteno>
            <urn:region>{{ $region }}</urn:region>
            <urn:regno>{{ $vehicle_number }}</urn:regno>
            <urn:renewno></urn:renewno>
            <urn:reqdatetime>{{ $timestamp }}</urn:reqdatetime>
            <urn:requestid>{{ $request_id }}</urn:requestid>
            <urn:safety>{{ $safety_code }}</urn:safety>
            <urn:seatcapacity>{{ $seat_capacity }}</urn:seatcapacity>
            <urn:signature>{{ $signature }}</urn:signature>
            <urn:stampduty>0</urn:stampduty>
            <urn:statecode>{{ $state_code }}</urn:statecode>
            <urn:suminsured>{{ $sum_insured }}</urn:suminsured>
            <urn:theftclaim>0</urn:theftclaim>
            <urn:thirdclaim>0</urn:thirdclaim>
            <urn:towndesc>{{ $town_description }}</urn:towndesc>
            <urn:trailerno />
            <urn:usecode>{{ $use_code }}</urn:usecode>
            <urn:vehbody>{{ $body_type_description }}</urn:vehbody>
            <urn:vehbodycode>{{ $body_type_code }}</urn:vehbodycode>
            <urn:vehcapacity>{{ $vehicle_capacity }}</urn:vehcapacity>
            <urn:vehcapacitycode>{{ $vehicle_capacity_code }}</urn:vehcapacitycode>
            <urn:vehclaim>0</urn:vehclaim>
            <urn:vehtypecode>{{ $vehicle_type_code }}</urn:vehtypecode>
            <urn:winclaim>0</urn:winclaim>
        </ser:reqdata>
    </soapenv:Body>
</soapenv:Envelope>
