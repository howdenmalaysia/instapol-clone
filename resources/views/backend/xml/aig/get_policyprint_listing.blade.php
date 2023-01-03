<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <getPolicyPrintListingReq xmlns="http://servlet">
            <agentcode xmlns="urn:GetPolicyPrintListing">{{ $agentcode }}</agentcode>
            <arrExtraParam xmlns="urn:GetPolicyPrintListing">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://d1.financial-link.com.my/AGS/services/GetPolicyPrintListing">
                    <paramIndicator xmlns="urn:GetRenewalData">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:GetRenewalData">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:GetRenewalData">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <bizregno xmlns="urn:GetPolicyPrintListing">{{ $bizregno }}</bizregno>
            <compcode xmlns="urn:GetPolicyPrintListing">{{ $compcode }}</compcode>
            <newic xmlns="urn:GetPolicyPrintListing">{{ $newic }}</newic>
            <oldic xmlns="urn:GetPolicyPrintListing">{{ $oldic }}</oldic>
            <passportno xmlns="urn:GetPolicyPrintListing">{{ $passportno }}</passportno>
            <requestid xmlns="urn:GetPolicyPrintListing">{{ $requestid }}</requestid>
            <signature xmlns="urn:GetPolicyPrintListing">{{ $signature }}</signature>
            <vehregno xmlns="urn:GetPolicyPrintListing">{{ $vehregno }}</vehregno>
        </getPolicyPrintListingReq>
    </Body>
</Envelope>