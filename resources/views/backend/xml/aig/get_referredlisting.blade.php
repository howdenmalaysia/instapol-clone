<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <getReferredListingReq xmlns="http://servlet">
            <agentcode xmlns="urn:GetReferredListing">{{ $agentcode }}</agentcode>
            <arrExtraParam xmlns="urn:GetReferredListing">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://www-400.aig.com.my/AGS/services/GetReferredListing">
                    <paramIndicator xmlns="urn:GetReferredListing">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:GetReferredListing">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:GetReferredListing">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <bizregno xmlns="urn:GetReferredListing">{{ $bizregno }}</bizregno>
            <compcode xmlns="urn:GetReferredListing">{{ $compcode }}</compcode>
            <newic xmlns="urn:GetReferredListing">{{ $newic }}</newic>
            <oldic xmlns="urn:GetReferredListing">{{ $oldic }}</oldic>
            <passportno xmlns="urn:GetReferredListing">{{ $passportno }}</passportno>
            <requestid xmlns="urn:GetReferredListing">{{ $requestid }}</requestid>
            <signature xmlns="urn:GetReferredListing">{{ $signature }}</signature>
            <vehregno xmlns="urn:GetReferredListing">{{ $vehregno }}</vehregno>
        </getReferredListingReq>
    </Body>
</Envelope>