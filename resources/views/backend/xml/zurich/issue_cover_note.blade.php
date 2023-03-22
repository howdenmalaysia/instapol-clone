<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
    <s:Body>
        <IssueCoverNote xmlns="https://gtws2.zurich.com.my/ziapps/zurichinsurance">
            <XmlRequest>
                <![CDATA[<RequestData>
                    <ParticipantDetails>
                        <ParticipantCode>{{ $participant_code }}</ParticipantCode>
                        <TransactionReferenceNo>{{ $transaction_reference }}</TransactionReferenceNo>
                        <RequestDateTime>{{ $request_datetime }}</RequestDateTime>
                        <HashCode>{{ $hashcode }}</HashCode>
                        <CNMailId>{{ $getmail }}</CNMailId>
                    </ParticipantDetails>
                    <IssueCoverNoteDetails>
                        <AgentCode>{{ $agent_code }}</AgentCode>
                        <QuotationNumber>{{ $quotation_no }}</QuotationNumber>
                        <LogBookNo>{{ $logbookno }}</LogBookNo>
                    </IssueCoverNoteDetails>	
                </RequestData>]]>
            </XmlRequest>
        </IssueCoverNote>
    </s:Body>
</s:Envelope>