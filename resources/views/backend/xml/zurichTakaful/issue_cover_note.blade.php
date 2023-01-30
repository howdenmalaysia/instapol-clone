<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <IssueCoverNote xmlns="https://api.zurich.com.my/v1/takaful/insurance/motor">
            <XmlRequest>
                <![CDATA[<RequestData>
                    <ParticipantDetails>
                        <ParticipantCode>{{ $participant_code }}</ParticipantCode>
                        <TransactionReferenceNo>{{ $transaction_reference }}</TransactionReferenceNo>
                        <RequestDateTime>{{ $request_datetime }}</RequestDateTime>
                        <HashCode>{{ $hashcode }}</HashCode>
                    </ParticipantDetails>
                    <IssueCoverNoteDetails>
                        <AgentCode>{{ $agent_code }}</AgentCode>
                        <QuotationNumber>{{ $quotation_no }}</QuotationNumber>
                    </IssueCoverNoteDetails>	
                </RequestData>]]>
            </XmlRequest>
        </IssueCoverNote>
    </s:Body>
</s:Envelope>