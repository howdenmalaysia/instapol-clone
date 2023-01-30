<table>
    <thead>
        <tr>
            <th>Amount</th>
            <th>Bank Code</th>
            <th>Bank Account</th>
            <th>Description_1</th>
            <th>Description_2</th>
            <th>Email To</th>
            <th>Email CC</th>
            <th>Email BCC</th>
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