<table>
    <thead>
        <tr>
            <th>Settlement</th>
            <th>Insurance ID</th>
            <th>Insurance Name</th>
            <th>Transaction Date</th>
            <th>Policy Start Date</th>
            <th>Policy Number</th>
            <th>Vehicle Number</th>
            <th>Policy Holder Name</th>
            <th>Policy Holder NRIC</th>
            <th>Policy Holder Phone Number</th>
            <th>Policy Holder Email</th>
            <th>Policy Holder Address</th>
            <th>Gross Premium</th>
            <th>Service Tax</th>
            <th>Stamp Duty</th>
            <th>Total Payable</th>
            <th>Net Premium</th>
            <th>Total Commission (10%)</th>
            <th>Discount (Total Payable)</th>
            <th>Discount (Premium Only)</th>
            <th>Discount (Road Tax Only)</th>
            <th>Digital / Physical RoadTax</th>
            <th>RoadTax Mailing Address</th>
            <th>RoadTax - Renewal Fee</th>
            <th>RoadTax - MYEG Fee</th>
            <th>RoadTax - eService Fee</th>
            <th>RoadTax - Service Tax</th>
            <th>Total Payable (Road Tax)</th>
            <th>Total Payable (incl Road Tax)</th>
            <th>Payment Gateway (Online Transfer)</th>
            <th>Payment Gateway (Credit Card)</th>
            <th>Payment Gateway (e-Wallet)</th>
            <th>Outstanding (eGHL)</th>
            <th>Net Transfer Amount (Insurer)</th>
            <th>Net Transfer Amount (Howden)</th>
            <th>Referrer</th>
            <th>Email Domain</th>
            <th>Promo Code</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $row)
            <tr>
                @foreach ($row as $value)
                    <td>{{ $value }}</td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
