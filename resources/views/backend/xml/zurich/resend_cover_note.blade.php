<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <ResendCoverNote xmlns="https://gtws2.zurich.com.my/ziapps/zurichinsurance">
            <XmlRequest>
                <![CDATA[<RequestData>
                    <ParticipantDetails>
                        <ParticipantCode>{{ $participant_code }}</ParticipantCode>
                        <TransactionReferenceNo>{{ $transaction_reference }}</TransactionReferenceNo>
                        <RequestDateTime>{{ $request_datetime }}</RequestDateTime>
                        <HashCode>{{ $hashcode }}</HashCode>
                    </ParticipantDetails>
                    <ResendCoverNoteDetails>
                        <AgentCode>{{ $agent_code }}</AgentCode>
                        <VehicleNumber>{{ $VehNo }}</VehicleNumber>
                        <CoverNoteNumber>{{ $cover_note_no }}</CoverNoteNumber>
                        <EmailTo>{{ $email_to }}</EmailTo>
                    </ResendCoverNoteDetails>
                </RequestData>]]>
            </XmlRequest>
        </ResendCoverNote >
    </s:Body>
</s:Envelope>