<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
   <s:Body>
      <getVixNcdReq xmlns="http://servlet">
         <agentcode xmlns="urn:GetVIXNCD">{{ $agent_code }}</agentcode>
         <arrExtraParam xmlns="urn:GetVIXNCD">
            @if (!empty($item))
                @foreach ($item as $items)
                  <item xmlns="https://d1.financial-link.com.my/AGS/services/GetVIXNCD">
                    <paramIndicator xmlns="urn:GetVIXNCD">{{ $items->paramIndicator }}</paramIndicator>
                    <paramRemark xmlns="urn:GetVIXNCD">{{ $items->paramRemark }}</paramRemark>
                    <paramValue xmlns="urn:GetVIXNCD">{{ $items->paramValue }}</paramValue>
                  </item>
                @endforeach
            @endif
         </arrExtraParam>
         <bizregno xmlns="urn:GetVIXNCD">{{ $biz_reg_no }}</bizregno>
         <compcode xmlns="urn:GetVIXNCD">{{ $comp_code }}</compcode>
         <newic xmlns="urn:GetVIXNCD">{{ $new_ic }}</newic>
         <oldic xmlns="urn:GetVIXNCD">{{ $old_ic }}</oldic>
         <passportno xmlns="urn:GetVIXNCD">{{ $passport_no }}</passportno>
         <regno xmlns="urn:GetVIXNCD">{{ $reg_no }}</regno>
         <requestid xmlns="urn:GetVIXNCD">{{ $request_id }}</requestid>
         <signature xmlns="urn:GetVIXNCD">{{ $signature }}</signature>
      </getVixNcdReq>
   </s:Body>
</s:Envelope>