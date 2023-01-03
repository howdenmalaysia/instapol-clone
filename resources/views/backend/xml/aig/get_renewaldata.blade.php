<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <getRenewalDataReq xmlns="http://servlet">
            <agentcode xmlns="urn:GetRenewalData">{{ $agentcode }}</agentcode>
            <arrExtraParam xmlns="urn:GetRenewalData">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://d1.financial-link.com.my/AGS/services/GetRenewalData">
                    <paramIndicator xmlns="urn:GetRenewalData">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:GetRenewalData">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:GetRenewalData">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <bizregno xmlns="urn:GetRenewalData">{{ $bizregno }}</bizregno>
            <compcode xmlns="urn:GetRenewalData">{{ $compcode }}</compcode>
            <newic xmlns="urn:GetRenewalData">{{ $newic }}</newic>
            <oldic xmlns="urn:GetRenewalData">{{ $oldic }}</oldic>
            <passportno xmlns="urn:GetRenewalData">{{ $passportno }}</passportno>
            <policyno xmlns="urn:GetRenewalData">{{ $policyno }}</policyno>
            <requestid xmlns="urn:GetRenewalData">{{ $requestid }}</requestid>
            <signature xmlns="urn:GetRenewalData">{{ $signature }}</signature>
            <vehregno xmlns="urn:GetRenewalData">{{ $vehregno }}</vehregno>
        </getRenewalDataReq>
    </Body>
</Envelope>