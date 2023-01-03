<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <getJPJStatusListingReq xmlns="http://servlet">
            <agentcode xmlns="urn:GetJPJStatusListing">{{ $agentcode }}</agentcode>
            <arrExtraParam xmlns="urn:GetJPJStatusListing">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://d1.financial-link.com.my/AGS/services/GetJPJStatusListing">
                    <paramIndicator xmlns="urn:GetJPJStatusListing">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:GetJPJStatusListing">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:GetJPJStatusListing">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <bizregno xmlns="urn:GetJPJStatusListing">{{ $bizregno }}</bizregno>
            <compcode xmlns="urn:GetJPJStatusListing">{{ $compcode }}</compcode>
            <newic xmlns="urn:GetJPJStatusListing">{{ $newic }}</newic>
            <oldic xmlns="urn:GetJPJStatusListing">{{ $oldic }}</oldic>
            <passportno xmlns="urn:GetJPJStatusListing">{{ $passportno }}</passportno>
            <requestid xmlns="urn:GetJPJStatusListing">{{ $requestid }}</requestid>
            <signature xmlns="urn:GetJPJStatusListing">{{ $signature }}</signature>
            <vehregno xmlns="urn:GetJPJStatusListing">{{ $vehregno }}</vehregno>
        </getJPJStatusListingReq>
    </Body>
</Envelope>