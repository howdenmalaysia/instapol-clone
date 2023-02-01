<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <reqdata xmlns="http://servlet">
            <arrExtraParam xmlns="urn:GetPolicyData">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://www-400.aig.com.my/AGS/services/GetPolicyData">
                    <paramIndicator xmlns="http://model">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="http://model">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="http://model">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <compcode xmlns="urn:GetPolicyData">{{ $compcode }}</compcode>
            <flquoteno xmlns="urn:GetPolicyData">{{ $flquoteno }}</flquoteno>
            <quoteno xmlns="urn:GetPolicyData">{{ $quoteno }}</quoteno>
            <requestid xmlns="urn:GetPolicyData">{{ $requestid }}</requestid>
            <signature xmlns="urn:GetPolicyData">{{ $signature }}</signature>
            <vehregno xmlns="urn:GetPolicyData">{{ $vehregno }}</vehregno>
        </reqdata>
    </Body>
</Envelope>