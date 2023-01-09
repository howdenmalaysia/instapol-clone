<{{"?"}}xml version="1.0" encoding="utf-8"{{"?"}}>
<Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <issueCoverNoteReq xmlns="http://servlet">
            <actprem xmlns="urn:IssueCoverNote">{{ $act_premium }}</actprem>
            @if (count($additional_driver) > 0)
            <adddrv xmlns="urn:IssueCoverNote">
                @foreach ($additional_driver as $index => $driver)
                    <item xmlns="{{ $domain . $path }}">
                        <drvage xmlns="urn:IssueCoverNote">{{ $driver->age }}</drvage>
                        <drvdob xmlns="urn:IssueCoverNote">{{ $driver->date_of_birth }}</drvdob>
                        <drvrel xmlns="urn:IssueCoverNote">{{ $driver->relationship }}</drvrel>
                        <icnumber xmlns="urn:IssueCoverNote">{{ $driver->id_number }}</icnumber>
                        <index xmlns="urn:IssueCoverNote">{{ $index + 1}}</index>
                        <name xmlns="urn:IssueCoverNote">{{ $driver->name }}</name>
                    </item>
                @endforeach
            </adddrv>
            @endif
            @if (count($extra_benefit) > 0)
                <extracov xmlns="urn:IssueCoverNote">
                    @foreach ($extra_benefit as $extra_cover)
                        <item xmlns="{{ $domain . $path }}">
                            <bencode xmlns="urn:IssueCoverNote">{{ $extra_cover->extra_cover_code }}</bencode>
                            <bendesc xmlns="urn:IssueCoverNote">{{ $extra_cover->extra_cover_description }}</bendesc>
                        </item>
                    @endforeach
                </extracov>
            @endif
            <address1 xmlns="urn:IssueCoverNote">{{ $address_1 }}</address1>
            <address2 xmlns="urn:IssueCoverNote">{{ $address_2 }}</address2>
            <address3 xmlns="urn:IssueCoverNote">{{ $address_3 }}</address3>
            <agentcode xmlns="urn:IssueCoverNote">{{ $agent_code }}</agentcode>
            <antitd xmlns="urn:IssueCoverNote">{{ $anti_theft }}</antitd>
            <birthdate xmlns="urn:IssueCoverNote">{{ $date_of_birth }}</birthdate>
            <chassisno xmlns="urn:IssueCoverNote">{{ $chassis_number }}</chassisno>
            <claimamt xmlns="urn:IssueCoverNote">0</claimamt>
            <cnpaystatus xmlns="urn:IssueCoverNote"></cnpaystatus>
            <commgstamt xmlns="urn:IssueCoverNote">0</commgstamt>
            <commiamt xmlns="urn:IssueCoverNote">0</commiamt>
            <commiperc xmlns="urn:IssueCoverNote">{{ $commission_percentage }}</commiperc>
            <compcode xmlns="urn:IssueCoverNote">{{ $company_code }}</compcode>
            <covercode xmlns="urn:IssueCoverNote">{{ $cover_code }}</covercode>
            <discountamt xmlns="urn:IssueCoverNote">0</discountamt>
            <discountperc xmlns="urn:IssueCoverNote">0</discountperc>
            <drvexp xmlns="urn:IssueCoverNote">{{ $driving_experience }}</drvexp>
            <effectivedate xmlns="urn:IssueCoverNote">{{ $effective_date }}</effectivedate>
            <effectivetime xmlns="urn:IssueCoverNote">{{ $effective_time }}</effectivetime>
            <email xmlns="urn:IssueCoverNote">{{ $email }}</email>
            <engineno xmlns="urn:IssueCoverNote">{{ $engine_number }}</engineno>
            <excess xmlns="urn:IssueCoverNote">{{ $excess }}</excess>
            <expirydate xmlns="urn:IssueCoverNote">{{ $expiry_date }}</expirydate>
            <flquoteno xmlns="urn:IssueCoverNote">{{ $fl_quote_number }}</flquoteno>
            <garage xmlns="urn:IssueCoverNote">{{ $garage_code }}</garage>
            <gender xmlns="urn:IssueCoverNote">{{ $gender }}</gender>
            <grossdue xmlns="urn:IssueCoverNote">{{ $gross_due }}</grossdue>
            <grossdue2 xmlns="urn:IssueCoverNote">{{ $gross_due_2 }}</grossdue2>
            <grossprem xmlns="urn:IssueCoverNote">{{ $gross_premium }}</grossprem>
            <gstamt xmlns="urn:IssueCoverNote">{{ $sst_amount }}</gstamt>
            <gstclaimperc xmlns="urn:IssueCoverNote">0</gstclaimperc>
            <gstperc xmlns="urn:IssueCoverNote">{{ $sst_percentage }}</gstperc>
            <gstpurpose xmlns="urn:IssueCoverNote" />
            <hphoneno xmlns="urn:IssueCoverNote">{{ $phone_number }}</hphoneno>
            <loadingamt xmlns="urn:IssueCoverNote">{{ $loading_amount }}</loadingamt>
            <loadingperc xmlns="urn:IssueCoverNote">{{ $loading_percentage }}</loadingperc>
            <makecodemajor xmlns="urn:IssueCoverNote">{{ $make_code }}</makecodemajor>
            <makecodeminor xmlns="urn:IssueCoverNote">{{ $model_code }}</makecodeminor>
            <makeyear xmlns="urn:IssueCoverNote">{{ $manufacture_year }}</makeyear>
            <maritalstatus xmlns="urn:IssueCoverNote">{{ $marital_status }}</maritalstatus>
            <ncdamt xmlns="urn:IssueCoverNote">{{ $ncd_amount }}</ncdamt>
            <ncdperc xmlns="urn:IssueCoverNote">{{ $ncd_percentage }}</ncdperc>
            <netdue xmlns="urn:IssueCoverNote">{{ $net_due }}</netdue>
            <netdue2 xmlns="urn:IssueCoverNote">{{ $net_due_2 }}</netdue2>
            <netprem xmlns="urn:IssueCoverNote">{{ $net_premium }}</netprem>
            <newic xmlns="urn:IssueCoverNote">{{ $id_number }}</newic>
            <occupmajor xmlns="urn:IssueCoverNote" />
            <oldic xmlns="urn:IssueCoverNote" />
            <ownershiptype xmlns="urn:IssueCoverNote">{{ $ownership_type }}</ownershiptype>
            <payamt xmlns="urn:IssueCoverNote">{{ $payment_amount }}</payamt>
            <piamdrv xmlns="urn:IssueCoverNote">{{ $piam_driver }}</piamdrv>
            <postcode xmlns="urn:IssueCoverNote">{{ $postcode }}</postcode>
            <preinsncd xmlns="urn:IssueCoverNote">{{ $previous_ncd_percentage }}</preinsncd>
            <pscoreoriloading xmlns="urn:IssueCoverNote">0</pscoreoriloading>
            <purchaseprice xmlns="urn:IssueCoverNote">0</purchaseprice>
            <purpose xmlns="urn:IssueCoverNote">{{ $purpose }}</purpose>
            <redbooksum xmlns="urn:IssueCoverNote">{{ $sum_insured }}</redbooksum>
            <region xmlns="urn:IssueCoverNote">{{ $region }}</region>
            <regno xmlns="urn:IssueCoverNote">{{ $vehicle_number }}</regno>
            <renewno xmlns="urn:IssueCoverNote">0</renewno>
            <requestid xmlns="urn:IssueCoverNote">{{ $request_id }}</requestid>
            <safety xmlns="urn:IssueCoverNote">{{ $safety_code }}</safety>
            <signature xmlns="urn:IssueCoverNote">{{ $signature }}</signature>
            <stampduty xmlns="urn:IssueCoverNote">{{ $stamp_duty }}</stampduty>
            <statecode xmlns="urn:IssueCoverNote" />
            <suminsured xmlns="urn:IssueCoverNote">{{ $sum_insured }}</suminsured>
            <tariffpremium xmlns="urn:IssueCoverNote">{{ $tariff_premium }}</tariffpremium>
            <theftclaim xmlns="urn:IssueCoverNote">0</theftclaim>
            <thirdclaim xmlns="urn:IssueCoverNote">0</thirdclaim>
            <usecode xmlns="urn:IssueCoverNote">{{ $vehicle_use_code }}</usecode>
            <vehbody xmlns="urn:IssueCoverNote">{{ $vehicle_body_description }}</vehbody>
            <vehbodycode xmlns="urn:IssueCoverNote">{{ $vehicle_body_code }}</vehbodycode>
            <vehcapacity xmlns="urn:IssueCoverNote">{{ $vehicle_capacity }}</vehcapacity>
            <vehcapacitycode xmlns="urn:IssueCoverNote">{{ $vehicle_capacity_code }}</vehcapacitycode>
            <vehclaim xmlns="urn:IssueCoverNote">0</vehclaim>
            <vehtypecode xmlns="urn:IssueCoverNote">{{ $vehicle_type_code }}</vehtypecode>
            <winclaim xmlns="urn:IssueCoverNote">0</winclaim>
        </issueCoverNoteReq>
    </Body>
</Envelope>