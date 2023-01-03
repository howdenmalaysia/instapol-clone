<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <saveReferredCaseReq xmlns="http://servlet">
            <GPSCertNo xmlns="urn:SaveReferredCase">{{ $GPSCertNo }}</GPSCertNo>
            <GPSCompName xmlns="urn:SaveReferredCase">{{ $GPSCompName }}</GPSCompName>
            <actprem xmlns="urn:SaveReferredCase">{{ $actprem }}</actprem>
            <adddrv xmlns="urn:SaveReferredCase">
            @if (!empty($additional_driver))
                @foreach ($additional_driver as $additional_drivers)
                <item xmlns="https://d1.financial-link.com.my/AGS/services/SaveReferredCase">
                    <drvage xmlns="urn:SaveReferredCase">{{ $drvage }}</drvage>
                    <drvdob xmlns="urn:SaveReferredCase">{{ $drvdob }}</drvdob>
                    <drvgender xmlns="urn:SaveReferredCase">{{ $drvgender }}</drvgender>
                    <drvmarital xmlns="urn:SaveReferredCase">{{ $drvmarital }}</drvmarital>
                    <drvoccup xmlns="urn:SaveReferredCase">{{ $drvoccup }}</drvoccup>
                    <drvrel xmlns="urn:SaveReferredCase">{{ $drvrel }}</drvrel>
                    <icnumber xmlns="urn:SaveReferredCase">{{ $icnumber }}</icnumber>
                    <index xmlns="urn:SaveReferredCase">{{ $index }}</index>
                    <name xmlns="urn:SaveReferredCase">{{ $name }}</name>
                    <oicnumber xmlns="urn:SaveReferredCase">{{ $oicnumber }}</oicnumber>
                </item>
                @endforeach
            @endif
            </adddrv>
            <address1 xmlns="urn:SaveReferredCase">{{ $address1 }}</address1>
            <address2 xmlns="urn:SaveReferredCase">{{ $address2 }}</address2>
            <address3 xmlns="urn:SaveReferredCase">{{ $address3 }}</address3>
            <agentcode xmlns="urn:SaveReferredCase">{{ $agentcode }}</agentcode>
            <agtgstregdate xmlns="urn:SaveReferredCase">{{ $agtgstregdate }}</agtgstregdate>
            <agtgstregno xmlns="urn:SaveReferredCase">{{ $agtgstregno }}</agtgstregno>
            <antitd xmlns="urn:SaveReferredCase">{{ $antitd }}</antitd>
            <arrExtraParam xmlns="urn:SaveReferredCase">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://d1.financial-link.com.my/AGS/services/SaveReferredCase">
                    <paramIndicator xmlns="urn:SaveReferredCase">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:SaveReferredCase">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:SaveReferredCase">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <bankappcode xmlns="urn:SaveReferredCase">{{ $bankappcode }}</bankappcode>
            <birthdate xmlns="urn:SaveReferredCase">{{ $birthdate }}</birthdate>
            <bizregno xmlns="urn:SaveReferredCase">{{ $bizregno }}</bizregno>
            <breakdownassist xmlns="urn:SaveReferredCase">{{ $breakdownassist }}</breakdownassist>
            <campaigncode xmlns="urn:SaveReferredCase">{{ $campaigncode }}</campaigncode>
            <channel xmlns="urn:SaveReferredCase">{{ $channel }}</channel>
            <chassisno xmlns="urn:SaveReferredCase">{{ $chassisno }}</chassisno>
            <claimamt xmlns="urn:SaveReferredCase">{{ $claimamt }}</claimamt>
            <commgstamt xmlns="urn:SaveReferredCase">{{ $commgstamt }}</commgstamt>
            <commiamt xmlns="urn:SaveReferredCase">{{ $commiamt }}</commiamt>
            <commiperc xmlns="urn:SaveReferredCase">{{ $commiperc }}</commiperc>
            <compcode xmlns="urn:SaveReferredCase">{{ $compcode }}</compcode>
            <country xmlns="urn:SaveReferredCase">{{ $country }}</country>
            <covercode xmlns="urn:SaveReferredCase">{{ $covercode }}</covercode>
            <discount xmlns="urn:SaveReferredCase">{{ $discount }}</discount>
            <discountamt xmlns="urn:SaveReferredCase">{{ $discountamt }}</discountamt>
            <discountperc xmlns="urn:SaveReferredCase">{{ $discountperc }}</discountperc>
            <drvexp xmlns="urn:SaveReferredCase">{{ $drvexp }}</drvexp>
            <effectivedate xmlns="urn:SaveReferredCase">{{ $effectivedate }}</effectivedate>
            <effectivetime xmlns="urn:SaveReferredCase">{{ $effectivetime }}</effectivetime>
            <email xmlns="urn:SaveReferredCase">{{ $email }}</email>
            <engineno xmlns="urn:SaveReferredCase">{{ $engineno }}</engineno>
            <excess xmlns="urn:SaveReferredCase">{{ $excess }}</excess>
            <expirydate xmlns="urn:SaveReferredCase">{{ $expirydate }}</expirydate>
            <extracov xmlns="urn:SaveReferredCase">
            @if (!empty($formatted_extra_cover))
                @foreach ($formatted_extra_cover as $extra_cover)
                <item xmlns="https://d1.financial-link.com.my/AGS/services/SaveReferredCase">
                    <bencode xmlns="urn:SaveReferredCase">{{ $bencode }}</bencode>
                    <bendesc xmlns="urn:SaveReferredCase">{{ $bendesc }}</bendesc>
                    <benpremium xmlns="urn:SaveReferredCase">{{ $benpremium }}</benpremium>
                    <cewcommperc xmlns="urn:SaveReferredCase">{{ $cewcommperc }}</cewcommperc>
                    <cewstampduty xmlns="urn:SaveReferredCase">{{ $cewstampduty }}</cewstampduty>
                    <suminsured xmlns="urn:SaveReferredCase">{{ $suminsured }}</suminsured>
                    <unit xmlns="urn:SaveReferredCase">{{ $unit }}</unit>
                </item>
                @endforeach
            @endif
            </extracov>
            <flquoteno xmlns="urn:SaveReferredCase">{{ $flquoteno }}</flquoteno>
            <garage xmlns="urn:SaveReferredCase">{{ $garage }}</garage>
            <gender xmlns="urn:SaveReferredCase">{{ $gender }}</gender>
            <grossdue xmlns="urn:SaveReferredCase">{{ $grossdue }}</grossdue>
            <grossdue2 xmlns="urn:SaveReferredCase">{{ $grossdue2 }}</grossdue2>
            <grossprem xmlns="urn:SaveReferredCase">{{ $grossprem }}</grossprem>
            <gstamt xmlns="urn:SaveReferredCase">{{ $gstamt }}</gstamt>
            <gstclaimperc xmlns="urn:SaveReferredCase">{{ $gstclaimperc }}</gstclaimperc>
            <gstcode xmlns="urn:SaveReferredCase">{{ $gstcode }}</gstcode>
            <gstoverwrite xmlns="urn:SaveReferredCase">{{ $gstoverwrite }}</gstoverwrite>
            <gstperc xmlns="urn:SaveReferredCase">{{ $gstperc }}</gstperc>
            <gstpurpose xmlns="urn:SaveReferredCase">{{ $gstpurpose }}</gstpurpose>
            <gstreg xmlns="urn:SaveReferredCase">{{ $gstreg }}</gstreg>
            <gstregdate xmlns="urn:SaveReferredCase">{{ $gstregdate }}</gstregdate>
            <gstregdateend xmlns="urn:SaveReferredCase">{{ $gstregdateend }}</gstregdateend>
            <hpcode xmlns="urn:SaveReferredCase">{{ $hpcode }}</hpcode>
            <hphoneno xmlns="urn:SaveReferredCase">{{ $hphoneno }}</hphoneno>
            <insertstmp xmlns="urn:SaveReferredCase">{{ $insertstmp }}</insertstmp>
            <insref2 xmlns="urn:SaveReferredCase">{{ $insref2 }}</insref2>
            <lessor xmlns="urn:SaveReferredCase">{{ $lessor }}</lessor>
            <loadingamt xmlns="urn:SaveReferredCase">{{ $loadingamt }}</loadingamt>
            <loadingperc xmlns="urn:SaveReferredCase">{{ $loadingperc }}</loadingperc>
            <makecodemajor xmlns="urn:SaveReferredCase">{{ $makecodemajor }}</makecodemajor>
            <makecodeminor xmlns="urn:SaveReferredCase">{{ $makecodeminor }}</makecodeminor>
            <makeyear xmlns="urn:SaveReferredCase">{{ $makeyear }}</makeyear>
            <maritalstatus xmlns="urn:SaveReferredCase">{{ $maritalstatus }}</maritalstatus>
            <name xmlns="urn:SaveReferredCase">{{ $name }}</name>
            <ncdamt xmlns="urn:SaveReferredCase">{{ $ncdamt }}</ncdamt>
            <ncdperc xmlns="urn:SaveReferredCase">{{ $ncdperc }}</ncdperc>
            <netdue xmlns="urn:SaveReferredCase">{{ $netdue }}</netdue>
            <netdue2 xmlns="urn:SaveReferredCase">{{ $netdue2 }}</netdue2>
            <netprem xmlns="urn:SaveReferredCase">{{ $netprem }}</netprem>
            <newic xmlns="urn:SaveReferredCase">{{ $newic }}</newic>
            <occupmajor xmlns="urn:SaveReferredCase">{{ $occupmajor }}</occupmajor>
            <oldic xmlns="urn:SaveReferredCase">{{ $oldic }}</oldic>
            <ownershiptype xmlns="urn:SaveReferredCase">{{ $ownershiptype }}</ownershiptype>
            <passportno xmlns="urn:SaveReferredCase">{{ $passportno }}</passportno>
            <payamt xmlns="urn:SaveReferredCase">{{ $payamt }}</payamt>
            <payno xmlns="urn:SaveReferredCase">{{ $payno }}</payno>
            <piamdrv xmlns="urn:SaveReferredCase">{{ $piamdrv }}</piamdrv>
            <postcode xmlns="urn:SaveReferredCase">{{ $postcode }}</postcode>
            <preinscode xmlns="urn:SaveReferredCase">{{ $preinscode }}</preinscode>
            <preinsname xmlns="urn:SaveReferredCase">{{ $preinsname }}</preinsname>
            <preinsncd xmlns="urn:SaveReferredCase">{{ $preinsncd }}</preinsncd>
            <preinspolno xmlns="urn:SaveReferredCase">{{ $preinspolno }}</preinspolno>
            <preinsregno xmlns="urn:SaveReferredCase">{{ $preinsregno }}</preinsregno>
            <prepoleffdate xmlns="urn:SaveReferredCase">{{ $prepoleffdate }}</prepoleffdate>
            <prepolexpdate xmlns="urn:SaveReferredCase">{{ $prepolexpdate }}</prepolexpdate>
            <pscoreoriloading xmlns="urn:SaveReferredCase">{{ $pscoreoriloading }}</pscoreoriloading>
            <purchasedate xmlns="urn:SaveReferredCase">{{ $purchasedate }}</purchasedate>
            <purchaseprice xmlns="urn:SaveReferredCase">{{ $purchaseprice }}</purchaseprice>
            <purpose xmlns="urn:SaveReferredCase">{{ $purpose }}</purpose>
            <quoteno xmlns="urn:SaveReferredCase">{{ $quoteno }}</quoteno>
            <receiptno xmlns="urn:SaveReferredCase">{{ $receiptno }}</receiptno>
            <redbookdesc xmlns="urn:SaveReferredCase">{{ $redbookdesc }}</redbookdesc>
            <redbooksum xmlns="urn:SaveReferredCase">{{ $redbooksum }}</redbooksum>
            <refercode xmlns="urn:SaveReferredCase">{{ $refercode }}</refercode>
            <referdesc xmlns="urn:SaveReferredCase">{{ $referdesc }}</referdesc>
            <region xmlns="urn:SaveReferredCase">{{ $region }}</region>
            <regno xmlns="urn:SaveReferredCase">{{ $regno }}</regno>
            <renewno xmlns="urn:SaveReferredCase">{{ $renewno }}</renewno>
            <requestid xmlns="urn:SaveReferredCase">{{ $requestid }}</requestid>
            <respdatetime xmlns="urn:SaveReferredCase">{{ $respdatetime }}</respdatetime>
            <safety xmlns="urn:SaveReferredCase">{{ $safety }}</safety>
            <signature xmlns="urn:SaveReferredCase">{{ $signature }}</signature>
            <stampduty xmlns="urn:SaveReferredCase">{{ $stampduty }}</stampduty>
            <statecode xmlns="urn:SaveReferredCase">{{ $statecode }}</statecode>
            <suminsured xmlns="urn:SaveReferredCase">{{ $suminsured }}</suminsured>
            <tariffpremium xmlns="urn:SaveReferredCase">{{ $tariffpremium }}</tariffpremium>
            <theftclaim xmlns="urn:SaveReferredCase">{{ $theftclaim }}</theftclaim>
            <thirdclaim xmlns="urn:SaveReferredCase">{{ $thirdclaim }}</thirdclaim>
            <towndesc xmlns="urn:SaveReferredCase">{{ $towndesc }}</towndesc>
            <trailerno xmlns="urn:SaveReferredCase">{{ $trailerno }}</trailerno>
            <usecode xmlns="urn:SaveReferredCase">{{ $usecode }}</usecode>
            <vehbody xmlns="urn:SaveReferredCase">{{ $vehbody }}</vehbody>
            <vehbodycode xmlns="urn:SaveReferredCase">{{ $vehbodycode }}</vehbodycode>
            <vehcapacity xmlns="urn:SaveReferredCase">{{ $vehcapacity }}</vehcapacity>
            <vehcapacitycode xmlns="urn:SaveReferredCase">{{ $vehcapacitycode }}</vehcapacitycode>
            <vehclaim xmlns="urn:SaveReferredCase">{{ $vehclaim }}</vehclaim>
            <vehtypecode xmlns="urn:SaveReferredCase">{{ $vehtypecode }}</vehtypecode>
            <waiveloading xmlns="urn:SaveReferredCase">{{ $waiveloading }}</waiveloading>
            <winclaim xmlns="urn:SaveReferredCase">{{ $winclaim }}</winclaim>
        </saveReferredCaseReq>
    </Body>
</Envelope>