<table>
    <thead>
        <tr>
            <th>Transaction ID</th>
            <th>Transaction Date &amp; Time</th>
            <th>Policy Start Date</th>
            <th>Policy Number</th>
            <th>Vehicle Number</th>
            <th>Policy Holder Name</th>
            <th>Gross Premium</th>
            <th>Service Tax</th>
            <th>Stamp Duty</th>
            <th>Total Payable</th>
            <th>Net Premium</th>
            <th>Net Transfer Amount</th>
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