<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <updJPJStatusRequest xmlns="http://servlet">
            <agentcode xmlns="urn:UpdJPJStatus">{{ $agentcode }}</agentcode>
            <arrExtraParam xmlns="urn:UpdJPJStatus">
            @if (!empty($item))
                @foreach ($item as $items)
                <item xmlns="https://www-400.aig.com.my/AGS/services/UpdJPJStatus">
                    <paramIndicator xmlns="urn:UpdJPJStatus">{{ $paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:UpdJPJStatus">{{ $paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:UpdJPJStatus">{{ $paramValue }}</paramValue>
                </item>
                @endforeach
            @endif
            </arrExtraParam>
            <bizregno xmlns="urn:UpdJPJStatus">{{ $bizregno }}</bizregno>
            <chassisno xmlns="urn:UpdJPJStatus">{{ $chassisno }}</chassisno>
            <cncondition xmlns="urn:UpdJPJStatus">{{ $cncondition }}</cncondition>
            <compcode xmlns="urn:UpdJPJStatus">{{ $compcode }}</compcode>
            <covernoteno xmlns="urn:UpdJPJStatus">{{ $covernoteno }}</covernoteno>
            <engineno xmlns="urn:UpdJPJStatus">{{ $engineno }}</engineno>
            <newic xmlns="urn:UpdJPJStatus">{{ $newic }}</newic>
            <oldic xmlns="urn:UpdJPJStatus">{{ $oldic }}</oldic>
            <passportno xmlns="urn:UpdJPJStatus">{{ $passportno }}</passportno>
            <requestid xmlns="urn:UpdJPJStatus">{{ $requestid }}</requestid>
            <signature xmlns="urn:UpdJPJStatus">{{ $signature }}</signature>
            <vehregno xmlns="urn:UpdJPJStatus">{{ $vehregno }}</vehregno>
        </updJPJStatusRequest>
    </Body>
</Envelope>