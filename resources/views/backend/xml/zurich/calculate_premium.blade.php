<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <CalculatePremium xmlns="https://gtws2.zurich.com.my/ziapps/zurichinsurance">
            <XmlRequest>
                <![CDATA[<RequestData>
                    <ParticipantDetails>
                        <ParticipantCode>{{ $participant_code }}</ParticipantCode>
                        <TransactionReferenceNo>{{ $transaction_reference }}</TransactionReferenceNo>
                        <RequestDateTime>{{ $request_datetime }}</RequestDateTime>
                        <HashCode>{{ $hashcode }}</HashCode>
                        <CNMailId>{{ $cn_mail }}</CNMailId>
                    </ParticipantDetails>
                    <BasicDetails>
                        <QuotationNo>{{ $quotation_no }}</QuotationNo>
                        <AgentCode>{{ $agent_code }}</AgentCode>
                        <TransType>{{ $trans_type }}</TransType>
                        <VehicleNo>{{ $veh_no }}</VehicleNo>
                        <PreVehicleNo>{{ $pre_veh_no }}</PreVehicleNo>
                        <ProductCode>{{ $product_code }}</ProductCode>
                        <CoverType>{{ $cover_type }}</CoverType>
                        <CICode>{{ $CI_code }}</CICode>
                        <EffDate>{{ $eff_date }}</EffDate>
                        <ExpDate>{{ $exp_date }}</ExpDate>
                        <NewOwnedVehInd>{{ $new_owned_veh_ind }}</NewOwnedVehInd>
                        <VehRegDate>{{ $veh_reg_date }}</VehRegDate>
                        <ReconInd>{{ $recon_ind }}</ReconInd>
                        <ModCarInd>{{ $mod_car_ind }}</ModCarInd>
                        <ModPerformanceAesthetic>{{ $mod_performance_aesthentic }}</ModPerformanceAesthetic>
                        <ModFunctional>{{ $mod_functional }}</ModFunctional>
                        <YearOfMake>{{ $year_of_make }}</YearOfMake>
                        <Make>{{ $make }}</Make>
                        <Model>{{ $model }}</Model>
                        <Capacity>{{ $capacity }}</Capacity>
                        <UOM>{{ $uom }}</UOM>
                        <EngineNo>{{ $engine_no }}</EngineNo>
                        <ChassisNo>{{ $chassis_no }}</ChassisNo>
                        <LogBookNo>{{ $logbook_no }}</LogBookNo>
                        <RegLoc>{{ $reg_loc }}</RegLoc>
                        <RegionCode>{{ $region_code }}</RegionCode>
                        <NoOfPassenger>{{ $no_of_passenger }}</NoOfPassenger>
                        <NoOfDrivers>{{ $no_of_drivers }}</NoOfDrivers>
                        <InsIndicator>{{ $ins_indicator }}</InsIndicator>
                        <Name>{{ $name }}</Name>
                        <InsNationality>{{ $ins_nationality }}</InsNationality>
                        <NewIC>{{ $new_ic }}</NewIC>
                        <OthersID>{{ $others_id }}</OthersID>
                        <DateOfBirth>{{ $date_of_birth }}</DateOfBirth>
                        <Age>{{ $age }}</Age>
                        <Gender>{{ $gender }}</Gender>
                        <MaritalSts>{{ $marital_sts }}</MaritalSts>
                        <Occupation>{{ $occupation }}</Occupation>
                        <MobileNo>{{ $mobile_no }}</MobileNo>
                        <OffPhNo>{{ $off_ph_no }}</OffPhNo>
                        <Email>{{ $email }}</Email>
                        <Address>{{ $address }}</Address>
                        <PostCode>{{ $post_code }}</PostCode>
                        <State>{{ $state }}</State>
                        <Country>{{ $country }}</Country>
                        <SumInsured>{{ $sum_insured }}</SumInsured>
                        <AVInd>{{ $av_ind }}</AVInd>
                        <VolExcess>{{ $vol_excess }}</VolExcess>
                        <PACInd>{{ $pac_ind }}</PACInd>
                        <PACType>{{ $pac_type }}</PACType>
                        <PACUnit>{{ $pac_unit }}</PACUnit>
                        <All_Driver_Ind>{{ $all_driver_ind }}</All_Driver_Ind>
                        <ABISI>{{ $abisi }}</ABISI>
                        <Chosen_SI_Type>{{ $chosen_si_type }}</Chosen_SI_Type>
                    </BasicDetails>
                    <ExtraCoverDetails>
                            <ExtraCoverData>
                                <ExtCovCode>{{ $ext_cov_code }}</ExtCovCode>
                                <UnitDay>{{ $unit_day }}</UnitDay>
                                <UnitAmount>{{ $unit_amount }}</UnitAmount>
                                <EffDate>{{ $ECD_eff_date }}</EffDate>
                                <ExpDate>{{ $ECD_exp_date }}</ExpDate>
                                <SumInsured>{{ $ECD_sum_insured }}</SumInsured>
                                <NoOfUnit>{{ $no_of_unit }}</NoOfUnit>
                            </ExtraCoverData>
                    </ExtraCoverDetails>
                    
                    @if (!empty($additional_driver))
                        <AdditionalNamedDriverDetails>
                            @foreach ($additional_driver as $additional_drivers)
                                <AdditionalNamedDriverData>
                                    <NdName>{{ $additional_drivers->nd_name }}</NdName>
                                    <NdIdentityNo>{{ $additional_drivers->nd_identity_no }}</NdIdentityNo>
                                    <NdDateOfBirth>{{ $additional_drivers->nd_date_of_birth }}</NdDateOfBirth>
                                    <NdGender>{{ $additional_drivers->nd_gender }}</NdGender>
                                    <NdMaritalSts>{{ $additional_drivers->nd_marital_sts }}</NdMaritalSts>
                                    <NdOccupation>{{ $additional_drivers->nd_occupation }}</NdOccupation>
                                    <NdRelationship>{{ $additional_drivers->nd_relationship }}</NdRelationship>
                                </AdditionalNamedDriverData>
                            @endforeach
                        </AdditionalNamedDriverDetails>
                    @endif
                    @if (!empty($pac_rider))
                        <PacRiderDetails>
                            @foreach ($pac_rider as $pac_riders)
                                <PacRiderData>
                                    <PacRiderNo>{{ $pac_riders->pac_rider_no }}</PacRiderNo>
                                    <PacRiderName>{{ $pac_riders->pac_rider_name }}</PacRiderName>
                                    <PacRiderNewIC>{{ $pac_riders->pac_rider_new_ic }}</PacRiderNewIC>
                                    <PacRiderOldIC>{{ $pac_riders->pac_rider_old_ic }}</PacRiderOldIC>
                                    <PacRiderDOB>{{ $pac_riders->pac_rider_dob }}</PacRiderDOB>
                                    <DefaultInd>{{ $pac_riders->default_ind }}</DefaultInd>
                                </PacRiderData>
                            @endforeach
                        </PacRiderDetails>
                    @endif
                    <PacExtraCoverDetails>
                        <PacExtraCoverData>
                            <PacCode>{{ $ecd_pac_code }}</PacCode>
                            <PacUnit>{{ $ecd_pac_unit }}</PacUnit>
                        </PacExtraCoverData>
                    </PacExtraCoverDetails>
                </RequestData>]]>
            </XmlRequest>
        </CalculatePremium>
    </s:Body>
</s:Envelope>