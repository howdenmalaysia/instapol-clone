<Envelope xmlns="http://schemas.xmlsoap.org/soap/envelope/">
    <Body>
        <PremiumRequest xmlns="http://tempuri.org/">
            <reqData>
                <allRider xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $all_rider }}</allRider>
                <altIDNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API"></altIDNo>
                <companyVehicle xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $is_company }}</companyVehicle>
                <coverage xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $coverage }}</coverage>
                <effDate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $effecitve_date }}</effDate>
                <expDate xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $expiry_date }}</expDate>
                @foreach ($extra_cover as $_extra_cover)
                    <extraCoverage xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">
                        <extraCoverageReq>
                            <coverageId>{{ $_extra_cover->extra_cover_code }}</coverageId>
                            <sumInsured>{{ $_extra_cover->sum_insured }}</sumInsured>
                        </extraCoverageReq>
                    </extraCoverage>
                @endforeach
                <gender xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $gender }}</gender>
                <insuredAge xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $age }}</insuredAge>
                <maritalStatus xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $marital_status }}</maritalStatus>
                <ncd xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $ncd_percentage }}</ncd>
                <noofNamedDriver xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $additional_driver_count }}</noofNamedDriver>
                <nricNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $ic_number }}</nricNo>
                <onHirePurchase xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $hire_purchase }}</onHirePurchase>
                <postcode xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $postcode }}</postcode>
                <refNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $reference_number }}</refNo>
                <sumInsured xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $sum_insured }}</sumInsured>
                <tokenId xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $token }}</tokenId>
                <vehID xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $nvic }}</vehID>
                <vehRegNo xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $vehicle_number }}</vehRegNo>
                <vehType xmlns="http://schemas.datacontract.org/2004/07/PO.Web.API">{{ $vehicle_type }}</vehType>
            </reqData>
        </PremiumRequest>
    </Body>
</Envelope>