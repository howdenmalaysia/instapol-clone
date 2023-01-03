<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <getReferredDataReq xmlns="http://servlet">
            <agentcode xmlns="urn:GetReferredData">{{ $agentcode }}</agentcode>
            <arrExtraParam xmlns="urn:GetReferredData">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://d1.financial-link.com.my/AGS/services/GetReferredData">
                    <paramIndicator xmlns="http://model">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="http://model">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="http://model">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <compcode xmlns="urn:GetReferredData">{{ $compcode }}</compcode>
            <flquoteno xmlns="urn:GetReferredData">{{ $flquoteno }}</flquoteno>
            <quoteno xmlns="urn:GetReferredData">{{ $quoteno }}</quoteno>
            <requestid xmlns="urn:GetReferredData">{{ $requestid }}</requestid>
            <respdatetime xmlns="urn:GetReferredData">{{ $respdatetime }}</respdatetime>
            <signature xmlns="urn:GetReferredData">{{ $signature }}</signature>
            <vehregno xmlns="urn:GetReferredData">{{ $vehregno }}</vehregno>
        </getReferredDataReq>
    </Body>
</Envelope>