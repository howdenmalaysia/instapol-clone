<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
   <s:Body>
        <reqdata xmlns="http://servlet">
            <addbendata xmlns="urn:GetPremium">
            @if (!empty($extra_benefit))
                @foreach ($extra_benefit as $benefits)
                <item xmlns="https://www-400.aig.com.my/AGS/services/GetPremium">
                    <bencode xmlns="urn:GetPremium">{{ $benefits->extra_cover_code }}</bencode>
                    <bendesc xmlns="urn:GetPremium">{{ $benefits->extra_cover_description }}</bendesc>
                    <benpremium xmlns="urn:GetPremium">{{ $benefits->premium }}</benpremium>
                    <cewcommperc xmlns="urn:GetPremium">{{ $benefits->commperc }}</cewcommperc>
                    <cewstampduty xmlns="urn:GetPremium">{{ $benefits->stampduty }}</cewstampduty>
                    <nominame xmlns="urn:GetPremium">{{ $benefits->name }}</nominame>
                    <nominewic xmlns="urn:GetPremium">{{ $benefits->newic }}</nominewic>
                    <suminsured xmlns="urn:GetPremium">{{ $benefits->sum_insured }}</suminsured>
                    <unit xmlns="urn:GetPremium">{{ $benefits->unit }}</unit>
                </item>
                @endforeach
            @endif
            </addbendata>
            <adddrvdata xmlns="urn:GetPremium">
            @if (!empty($add_driver))
                @foreach ($add_driver as $drivers)
                <item xmlns="https://www-400.aig.com.my/AGS/services/GetPremium">
                    <drvage xmlns="urn:GetPremium">{{ $drivers->age }}</drvage>
                    <drvdob xmlns="urn:GetPremium">{{ $drivers->dob }}</drvdob>
                    <drvexp xmlns="urn:GetPremium">{{ $drivers->exp }}</drvexp>
                    <drvgender xmlns="urn:GetPremium">{{ $drivers->gender }}</drvgender>
                    <drvmarital xmlns="urn:GetPremium">{{ $drivers->marital }}</drvmarital>
                    <drvoccup xmlns="urn:GetPremium">{{ $drivers->occup }}</drvoccup>
                    <drvrel xmlns="urn:GetPremium">{{ $drivers->rel }}</drvrel>
                    <icnumber xmlns="urn:GetPremium">{{ $drivers->icno }}</icnumber>
                    <index xmlns="urn:GetPremium">{{ $drivers->index }}</index>
                    <name xmlns="urn:GetPremium">{{ $drivers->name }}</name>
                    <oicnumber xmlns="urn:GetPremium">{{ $drivers->oicno }}</oicnumber>
                </item>
                @endforeach
            @endif
            </adddrvdata>
            <address1 xmlns="urn:GetPremium">{{ $address_1 }}</address1>
            <address2 xmlns="urn:GetPremium">{{ $address_2 }}</address2>
            <address3 xmlns="urn:GetPremium">{{ $address_3 }}</address3>
            <agentcode xmlns="urn:GetPremium">{{ $agent_code }}</agentcode>
            <agtgstregdate xmlns="urn:GetPremium">{{ $agtgstregdate }}</agtgstregdate>
            <agtgstregno xmlns="urn:GetPremium">{{ $agtgstregno }}</agtgstregno>
            <antitd xmlns="urn:GetPremium">{{ $antitd }}</antitd>
            <birthdate xmlns="urn:GetPremium">{{ $birthdate }}</birthdate>
            <bizregno xmlns="urn:GetPremium">{{ $bizregno }}</bizregno>
            <channel xmlns="urn:GetPremium">{{ $channel }}</channel>
            <chassisno xmlns="urn:GetPremium">{{ $chassisno }}</chassisno>
            <claimamt xmlns="urn:GetPremium">{{ $claimamt }}</claimamt>
            <cncondition xmlns="urn:GetPremium">{{ $cncondition }}</cncondition>
            <commiperc xmlns="urn:GetPremium">{{ $commiperc }}</commiperc>
            <compcode xmlns="urn:GetPremium">{{ $compcode }}</compcode>
            <country xmlns="urn:GetPremium">{{ $country }}</country>
            <covercode xmlns="urn:GetPremium">{{ $covercode }}</covercode>
            <discount xmlns="urn:GetPremium">{{ $discount }}</discount>
            <discountperc xmlns="urn:GetPremium">{{ $discountperc }}</discountperc>
            <driveexp xmlns="urn:GetPremium">{{ $driveexp }}</driveexp>
            <effectivedate xmlns="urn:GetPremium">{{ $effectivedate }}</effectivedate>
            <effectivetime xmlns="urn:GetPremium">{{ $effectivetime }}</effectivetime>
            <email xmlns="urn:GetPremium">{{ $email }}</email>
            <engineno xmlns="urn:GetPremium">{{ $engineno }}</engineno>
            <expirydate xmlns="urn:GetPremium">{{ $expirydate }}</expirydate>
            <extraParam xmlns="urn:GetPremium">
            @if (!empty($item))
                @foreach ($item as $items)
                  <item xmlns="https://www-400.aig.com.my/AGS/services/GetPremium">
                    <paramIndicator xmlns="urn:GetPremium">{{ $items->paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:GetPremium">{{ $items->paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:GetPremium">{{ $items->paramValue }}</paramValue>
                  </item>
                @endforeach
            @endif
            </extraParam>
            <garage xmlns="urn:GetPremium">{{ $garage }}</garage>
            <gender xmlns="urn:GetPremium">{{ $gender }}</gender>
            <gstclaimperc xmlns="urn:GetPremium">{{ $gstclaimperc }}</gstclaimperc>
            <gstcode xmlns="urn:GetPremium">{{ $gstcode }}</gstcode>
            <gstpurpose xmlns="urn:GetPremium">{{ $gstpurpose }}</gstpurpose>
            <gstreg xmlns="urn:GetPremium">{{ $gstreg }}</gstreg>
            <gstregdate xmlns="urn:GetPremium">{{ $gstregdate }}</gstregdate>
            <gstregdateend xmlns="urn:GetPremium">{{ $gstregdateend }}</gstregdateend>
            <gstregno xmlns="urn:GetPremium">{{ $gstregno }}</gstregno>
            <hpcode xmlns="urn:GetPremium">{{ $hpcode }}</hpcode>
            <hphoneno xmlns="urn:GetPremium">{{ $hphoneno }}</hphoneno>
            <lessor xmlns="urn:GetPremium">{{ $lessor }}</lessor>
            <loadingamt xmlns="urn:GetPremium">{{ $loadingamt }}</loadingamt>
            <loadingperc xmlns="urn:GetPremium">{{ $loadingperc }}</loadingperc>
            <makecodemajor xmlns="urn:GetPremium">{{ $makecodemajor }}</makecodemajor>
            <makecodeminor xmlns="urn:GetPremium">{{ $makecodeminor }}</makecodeminor>
            <makeyear xmlns="urn:GetPremium">{{ $makeyear }}</makeyear>
            <maritalstatus xmlns="urn:GetPremium">{{ $maritalstatus }}</maritalstatus>
            <mtcycrider xmlns="urn:GetPremium">{{ $mtcycrider }}</mtcycrider>
            <name xmlns="urn:GetPremium">{{ $name }}</name>
            <ncdamt xmlns="urn:GetPremium">{{ $ncdamt }}</ncdamt>
            <ncdperc xmlns="urn:GetPremium">{{ $ncdperc }}</ncdperc>
            <newic xmlns="urn:GetPremium">{{ $newic }}</newic>
            <occupmajor xmlns="urn:GetPremium">{{ $occupmajor }}</occupmajor>
            <oldic xmlns="urn:GetPremium">{{ $oldic }}</oldic>
            <ownershiptype xmlns="urn:GetPremium">{{ $ownershiptype }}</ownershiptype>
            <passportno xmlns="urn:GetPremium">{{ $passportno }}</passportno>
            <piamdrv xmlns="urn:GetPremium">{{ $piamdrv }}</piamdrv>
            <postcode xmlns="urn:GetPremium">{{ $postcode }}</postcode>
            <preinscode xmlns="urn:GetPremium">{{ $preinscode }}</preinscode>
            <preinsname xmlns="urn:GetPremium">{{ $preinsname }}</preinsname>
            <preinsncd xmlns="urn:GetPremium">{{ $preinsncd }}</preinsncd>
            <preinspolno xmlns="urn:GetPremium">{{ $preinspolno }}</preinspolno>
            <preinsregno xmlns="urn:GetPremium">{{ $preinsregno }}</preinsregno>
            <prepoleffdate xmlns="urn:GetPremium">{{ $prepoleffdate }}</prepoleffdate>
            <prepolexpdate xmlns="urn:GetPremium">{{ $prepolexpdate }}</prepolexpdate>
            <purchasedate xmlns="urn:GetPremium">{{ $purchasedate }}</purchasedate>
            <purchaseprice xmlns="urn:GetPremium">{{ $purchaseprice }}</purchaseprice>
            <purpose xmlns="urn:GetPremium">{{ $purpose }}</purpose>
            <quoteno xmlns="urn:GetPremium">{{ $quoteno }}</quoteno>
            <region xmlns="urn:GetPremium">{{ $region }}</region>
            <regno xmlns="urn:GetPremium">{{ $regno }}</regno>
            <renewno xmlns="urn:GetPremium">{{ $renewno }}</renewno>
            <reqdatetime xmlns="urn:GetPremium">{{ $reqdatetime }}</reqdatetime>
            <requestid xmlns="urn:GetPremium">{{ $requestid }}</requestid>
            <safety xmlns="urn:GetPremium">{{ $safety }}</safety>
            <seatcapacity xmlns="urn:GetPremium">{{ $seatcapacity }}</seatcapacity>
            <signature xmlns="urn:GetPremium">{{ $signature }}</signature>
            <stampduty xmlns="urn:GetPremium">{{ $stampduty }}</stampduty>
            <statecode xmlns="urn:GetPremium">{{ $statecode }}</statecode>
            <suminsured xmlns="urn:GetPremium">{{ $suminsured }}</suminsured>
            <theftclaim xmlns="urn:GetPremium">{{ $theftclaim }}</theftclaim>
            <thirdclaim xmlns="urn:GetPremium">{{ $thirdclaim }}</thirdclaim>
            <towndesc xmlns="urn:GetPremium">{{ $towndesc }}</towndesc>
            <trailerno xmlns="urn:GetPremium">{{ $trailerno }}</trailerno>
            <usecode xmlns="urn:GetPremium">{{ $usecode }}</usecode>
            <vehbody xmlns="urn:GetPremium">{{ $vehbody }}</vehbody>
            <vehbodycode xmlns="urn:GetPremium">{{ $vehbodycode }}</vehbodycode>
            <vehcapacity xmlns="urn:GetPremium">{{ $vehcapacity }}</vehcapacity>
            <vehcapacitycode xmlns="urn:GetPremium">{{ $vehcapacitycode }}</vehcapacitycode>
            <vehclaim xmlns="urn:GetPremium">{{ $vehclaim }}</vehclaim>
            <vehtypecode xmlns="urn:GetPremium">{{ $vehtypecode }}</vehtypecode>
            <winclaim xmlns="urn:GetPremium">{{ $winclaim }}</winclaim>
        </reqdata>
   </s:Body>
</s:Envelope>