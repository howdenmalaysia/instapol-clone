<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <GetJPJStatus xmlns="https://api.zurich.com.my/v1/general/insurance/motor">
            <XmlRequest>
            <![CDATA[<RequestData>
                    <ParticipantDetails>
                        <ParticipantCode>{{ $participant_code }}</ParticipantCode>
                        <TransactionReferenceNo>{{ $transaction_reference }}</TransactionReferenceNo>
                        <RequestDateTime>{{ $request_datetime }}</RequestDateTime>
                        <HashCode>{{ $hashcode }}</HashCode>
                    </ParticipantDetails>
                    <JPJStatusDetails>
                        <AgentCode>{{ $agent_code }}</AgentCode>
                        <CoverNoteNumber>{{ $cover_note_no }}</CoverNoteNumber>
                    </JPJStatusDetails>
                </RequestData>]]>
            </XmlRequest>
        </GetJPJStatus>
    </s:Body>
</s:Envelope>