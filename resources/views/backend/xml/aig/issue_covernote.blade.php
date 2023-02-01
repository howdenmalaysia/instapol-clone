<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <issueCoverNoteReq xmlns="http://servlet">
            <GPSCertNo xmlns="urn:IssueCoverNote">{{ $GPSCertNo }}</GPSCertNo>
            <GPSCompName xmlns="urn:IssueCoverNote">{{ $GPSCompName }}</GPSCompName>
            <actprem xmlns="urn:IssueCoverNote">{{ $actprem }}</actprem>
            <adddrv xmlns="urn:IssueCoverNote">
            @if (!empty($additional_driver))
                @foreach ($additional_driver as $additional_drivers)
                    <item xmlns="https://www-400.aig.com.my/AGS/services/IssueCoverNote">
                        <drvage xmlns="urn:IssueCoverNote">{{ $additional_drivers->drvage }}</drvage>
                        <drvdob xmlns="urn:IssueCoverNote">{{ $additional_drivers->drvdob }}</drvdob>
                        <drvgender xmlns="urn:IssueCoverNote">{{ $additional_drivers->drvgender }}</drvgender>
                        <drvmarital xmlns="urn:IssueCoverNote">{{ $additional_drivers->drvmarital }}</drvmarital>
                        <drvoccup xmlns="urn:IssueCoverNote">{{ $additional_drivers->drvoccup }}</drvoccup>
                        <drvrel xmlns="urn:IssueCoverNote">{{ $additional_drivers->drvrel }}</drvrel>
                        <icnumber xmlns="urn:IssueCoverNote">{{ $additional_drivers->icnumber }}</icnumber>
                        <index xmlns="urn:IssueCoverNote">{{ $additional_drivers->index }}</index>
                        <name xmlns="urn:IssueCoverNote">{{ $additional_drivers->name }}</name>
                        <oicnumber xmlns="urn:IssueCoverNote">{{ $additional_drivers->oicnumber }}</oicnumber>
                    </item>
                @endforeach
            @endif
            </adddrv>
            <address1 xmlns="urn:IssueCoverNote">{{ $address1 }}</address1>
            <address2 xmlns="urn:IssueCoverNote">{{ $address2 }}</address2>
            <address3 xmlns="urn:IssueCoverNote">{{ $address3 }}</address3>
            <agentcode xmlns="urn:IssueCoverNote">{{ $agentcode }}</agentcode>
            <agtgstregdate xmlns="urn:IssueCoverNote">{{ $agtgstregdate }}</agtgstregdate>
            <agtgstregno xmlns="urn:IssueCoverNote">{{ $agtgstregno }}</agtgstregno>
            <antitd xmlns="urn:IssueCoverNote">{{ $antitd }}</antitd>
            <arrExtraParam xmlns="urn:IssueCoverNote">            
            @if (!empty($item))
                @foreach ($item as $items)
                    <item xmlns="https://www-400.aig.com.my/AGS/services/IssueCoverNote">
                        <paramIndicator xmlns="urn:IssueCoverNote">{{ $items->paramIndicator }}</paramIndicator>
                        <paramRemark xmlns="urn:IssueCoverNote">{{ $items->paramRemark }}</paramRemark>
                        <paramValue xmlns="urn:IssueCoverNote">{{ $items->paramValue }}</paramValue>
                    </item>
                @endforeach
            @endif
            </arrExtraParam>
            <bankappcode xmlns="urn:IssueCoverNote">{{ $bankappcode }}</bankappcode>
            <birthdate xmlns="urn:IssueCoverNote">{{ $birthdate }}</birthdate>
            <bizregno xmlns="urn:IssueCoverNote">{{ $bizregno }}</bizregno>
            <breakdownassist xmlns="urn:IssueCoverNote">{{ $breakdownassist }}</breakdownassist>
            <campaigncode xmlns="urn:IssueCoverNote">{{ $campaigncode }}</campaigncode>
            <ccardexpdt xmlns="urn:IssueCoverNote">{{ $ccardexpdt }}</ccardexpdt>
            <ccardtype xmlns="urn:IssueCoverNote">{{ $ccardtype }}</ccardtype>
            <channel xmlns="urn:IssueCoverNote">{{ $channel }}</channel>
            <chassisno xmlns="urn:IssueCoverNote">{{ $chassisno }}</chassisno>
            <claimamt xmlns="urn:IssueCoverNote">{{ $claimamt }}</claimamt>
            <cncondition xmlns="urn:IssueCoverNote">{{ $cncondition }}</cncondition>
            <cnpaystatus xmlns="urn:IssueCoverNote">{{ $cnpaystatus }}</cnpaystatus>
            <commgstamt xmlns="urn:IssueCoverNote">{{ $commgstamt }}</commgstamt>
            <commiamt xmlns="urn:IssueCoverNote">{{ $commiamt }}</commiamt>
            <commiperc xmlns="urn:IssueCoverNote">{{ $commiperc }}</commiperc>
            <compcode xmlns="urn:IssueCoverNote">{{ $compcode }}</compcode>
            <country xmlns="urn:IssueCoverNote">{{ $country }}</country>
            <covercode xmlns="urn:IssueCoverNote">{{ $covercode }}</covercode>
            <discount xmlns="urn:IssueCoverNote">{{ $discount }}</discount>
            <discountamt xmlns="urn:IssueCoverNote">{{ $discountamt }}</discountamt>
            <discountperc xmlns="urn:IssueCoverNote">{{ $discountperc }}</discountperc>
            <drvexp xmlns="urn:IssueCoverNote">{{ $drvexp }}</drvexp>
            <effectivedate xmlns="urn:IssueCoverNote">{{ $effectivedate }}</effectivedate>
            <effectivetime xmlns="urn:IssueCoverNote">{{ $effectivetime }}</effectivetime>
            <email xmlns="urn:IssueCoverNote">{{ $email }}</email>
            <engineno xmlns="urn:IssueCoverNote">{{ $engineno }}</engineno>
            <excess xmlns="urn:IssueCoverNote">{{ $excess }}</excess>
            <expirydate xmlns="urn:IssueCoverNote">{{ $expirydate }}</expirydate>
            <extracov xmlns="urn:IssueCoverNote">
            @if (!empty($formatted_extra_cover))
                @foreach ($formatted_extra_cover as $extra_cover)
                    <item xmlns="https://www-400.aig.com.my/AGS/services/IssueCoverNote">
                        <bencode xmlns="urn:IssueCoverNote">{{ $extra_cover->bencode }}</bencode>
                        <bendesc xmlns="urn:IssueCoverNote">{{ $extra_cover->bendesc }}</bendesc>
                        <benpremium xmlns="urn:IssueCoverNote">{{ $extra_cover->benpremium }}</benpremium>
                        <cewcommperc xmlns="urn:IssueCoverNote">{{ $extra_cover->cewcommperc }}</cewcommperc>
                        <cewstampduty xmlns="urn:IssueCoverNote">{{ $extra_cover->cewstampduty }}</cewstampduty>
                        <suminsured xmlns="urn:IssueCoverNote">{{ $extra_cover->suminsured }}</suminsured>
                        <unit xmlns="urn:IssueCoverNote">{{ $extra_cover->unit }}</unit>
                    </item>
                @endforeach
            @endif
            </extracov>
            <flquoteno xmlns="urn:IssueCoverNote">{{ $flquoteno }}</flquoteno>
            <garage xmlns="urn:IssueCoverNote">{{ $garage }}</garage>
            <gender xmlns="urn:IssueCoverNote">{{ $gender }}</gender>
            <grossdue xmlns="urn:IssueCoverNote">{{ $grossdue }}</grossdue>
            <grossdue2 xmlns="urn:IssueCoverNote">{{ $grossdue2 }}</grossdue2>
            <grossprem xmlns="urn:IssueCoverNote">{{ $grossprem }}</grossprem>
            <gstamt xmlns="urn:IssueCoverNote">{{ $gstamt }}</gstamt>
            <gstclaimperc xmlns="urn:IssueCoverNote">{{ $gstclaimperc }}</gstclaimperc>
            <gstcode xmlns="urn:IssueCoverNote">{{ $gstcode }}</gstcode>
            <gstoverwrite xmlns="urn:IssueCoverNote">{{ $gstoverwrite }}</gstoverwrite>
            <gstperc xmlns="urn:IssueCoverNote">{{ $gstperc }}</gstperc>
            <gstpurpose xmlns="urn:IssueCoverNote">{{ $gstpurpose }}</gstpurpose>
            <gstreg xmlns="urn:IssueCoverNote">{{ $gstreg }}</gstreg>
            <gstregdate xmlns="urn:IssueCoverNote">{{ $gstregdate }}</gstregdate>
            <gstregdateend xmlns="urn:IssueCoverNote">{{ $gstregdateend }}</gstregdateend>
            <gstregno xmlns="urn:IssueCoverNote">{{ $gstregno }}</gstregno>
            <hpcode xmlns="urn:IssueCoverNote">{{ $hpcode }}</hpcode>
            <hphoneno xmlns="urn:IssueCoverNote">{{ $hphoneno }}</hphoneno>
            <insertstmp xmlns="urn:IssueCoverNote">{{ $insertstmp }}</insertstmp>
            <insref2 xmlns="urn:IssueCoverNote">{{ $insref2 }}</insref2>
            <last4digit xmlns="urn:IssueCoverNote">{{ $last4digit }}</last4digit>
            <lessor xmlns="urn:IssueCoverNote">{{ $lessor }}</lessor>
            <loadingamt xmlns="urn:IssueCoverNote">{{ $loadingamt }}</loadingamt>
            <loadingperc xmlns="urn:IssueCoverNote">{{ $loadingperc }}</loadingperc>
            <makecodemajor xmlns="urn:IssueCoverNote">{{ $makecodemajor }}</makecodemajor>
            <makecodeminor xmlns="urn:IssueCoverNote">{{ $makecodeminor }}</makecodeminor>
            <makeyear xmlns="urn:IssueCoverNote">{{ $makeyear }}</makeyear>
            <maritalstatus xmlns="urn:IssueCoverNote">{{ $maritalstatus }}</maritalstatus>
            <merchantid xmlns="urn:IssueCoverNote">{{ $merchantid }}</merchantid>
            <name xmlns="urn:IssueCoverNote">{{ $name }}</name>
            <ncdamt xmlns="urn:IssueCoverNote">{{ $ncdamt }}</ncdamt>
            <ncdperc xmlns="urn:IssueCoverNote">{{ $ncdperc }}</ncdperc>
            <netdue xmlns="urn:IssueCoverNote">{{ $netdue }}</netdue>
            <netdue2 xmlns="urn:IssueCoverNote">{{ $netdue2 }}</netdue2>
            <netprem xmlns="urn:IssueCoverNote">{{ $netprem }}</netprem>
            <newic xmlns="urn:IssueCoverNote">{{ $newic }}</newic>
            <occupmajor xmlns="urn:IssueCoverNote">{{ $occupmajor }}</occupmajor>
            <oldic xmlns="urn:IssueCoverNote">{{ $oldic }}</oldic>
            <ownershiptype xmlns="urn:IssueCoverNote">{{ $ownershiptype }}</ownershiptype>
            <passportno xmlns="urn:IssueCoverNote">{{ $passportno }}</passportno>
            <payamt xmlns="urn:IssueCoverNote">{{ $payamt }}</payamt>
            <paytype xmlns="urn:IssueCoverNote">{{ $paytype }}</paytype>
            <piamdrv xmlns="urn:IssueCoverNote">{{ $piamdrv }}</piamdrv>
            <postcode xmlns="urn:IssueCoverNote">{{ $postcode }}</postcode>
            <preinscode xmlns="urn:IssueCoverNote">{{ $preinscode }}</preinscode>
            <preinsname xmlns="urn:IssueCoverNote">{{ $preinsname }}</preinsname>
            <preinsncd xmlns="urn:IssueCoverNote">{{ $preinsncd }}</preinsncd>
            <preinspolno xmlns="urn:IssueCoverNote">{{ $preinspolno }}</preinspolno>
            <preinsregno xmlns="urn:IssueCoverNote">{{ $preinsregno }}</preinsregno>
            <prepoleffdate xmlns="urn:IssueCoverNote">{{ $prepoleffdate }}</prepoleffdate>
            <prepolexpdate xmlns="urn:IssueCoverNote">{{ $prepolexpdate }}</prepolexpdate>
            <pscoreoriloading xmlns="urn:IssueCoverNote">{{ $pscoreoriloading }}</pscoreoriloading>
            <purchasedate xmlns="urn:IssueCoverNote">{{ $purchasedate }}</purchasedate>
            <purchaseprice xmlns="urn:IssueCoverNote">{{ $purchaseprice }}</purchaseprice>
            <purpose xmlns="urn:IssueCoverNote">{{ $purpose }}</purpose>
            <quoteno xmlns="urn:IssueCoverNote">{{ $quoteno }}</quoteno>
            <receiptno xmlns="urn:IssueCoverNote">{{ $receiptno }}</receiptno>
            <redbookdesc xmlns="urn:IssueCoverNote">{{ $redbookdesc }}</redbookdesc>
            <redbooksum xmlns="urn:IssueCoverNote">{{ $redbooksum }}</redbooksum>
            <region xmlns="urn:IssueCoverNote">{{ $region }}</region>
            <regno xmlns="urn:IssueCoverNote">{{ $regno }}</regno>
            <renewno xmlns="urn:IssueCoverNote">{{ $renewno }}</renewno>
            <requestid xmlns="urn:IssueCoverNote">{{ $requestid }}</requestid>
            <respdatetime xmlns="urn:IssueCoverNote">{{ $respdatetime }}</respdatetime>
            <safety xmlns="urn:IssueCoverNote">{{ $safety }}</safety>
            <signature xmlns="urn:IssueCoverNote">{{ $signature }}</signature>
            <stampduty xmlns="urn:IssueCoverNote">{{ $stampduty }}</stampduty>
            <statecode xmlns="urn:IssueCoverNote">{{ $statecode }}</statecode>
            <suminsured xmlns="urn:IssueCoverNote">{{ $suminsured }}</suminsured>
            <tariffpremium xmlns="urn:IssueCoverNote">{{ $tariffpremium }}</tariffpremium>
            <theftclaim xmlns="urn:IssueCoverNote">{{ $theftclaim }}</theftclaim>
            <thirdclaim xmlns="urn:IssueCoverNote">{{ $thirdclaim }}</thirdclaim>
            <towndesc xmlns="urn:IssueCoverNote">{{ $towndesc }}</towndesc>
            <trailerno xmlns="urn:IssueCoverNote">{{ $trailerno }}</trailerno>
            <usecode xmlns="urn:IssueCoverNote">{{ $usecode }}</usecode>
            <vehbody xmlns="urn:IssueCoverNote">{{ $vehbody }}</vehbody>
            <vehbodycode xmlns="urn:IssueCoverNote">{{ $vehbodycode }}</vehbodycode>
            <vehcapacity xmlns="urn:IssueCoverNote">{{ $vehcapacity }}</vehcapacity>
            <vehcapacitycode xmlns="urn:IssueCoverNote">{{ $vehcapacitycode }}</vehcapacitycode>
            <vehclaim xmlns="urn:IssueCoverNote">{{ $vehclaim }}</vehclaim>
            <vehtypecode xmlns="urn:IssueCoverNote">{{ $vehtypecode }}</vehtypecode>
            <waiveloading xmlns="urn:IssueCoverNote">{{ $waiveloading }}</waiveloading>
            <winclaim xmlns="urn:IssueCoverNote">{{ $winclaim }}</winclaim>
        </issueCoverNoteReq>
    </Body>
</Envelope>