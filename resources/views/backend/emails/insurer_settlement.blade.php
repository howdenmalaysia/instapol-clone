<p>Attached is the instaPol settlement details for {{ $start_date }}</p>

<p><b>Howden:</b></p>
<p>
    Total Commission = RM {{ number_format($total_commission, 2) }}
    Total eService Fee = RM {{ number_format($total_eservice_fee, 2) }}
    Total SST = RM {{ number_format($total_sst, 2) }}
    Total Discount = RM {{ number_format($total_discount, 2) }}
    Total Payment Gateway Charges = RM {{ number_format($total_payment_gateway_charges, 2) }}
</p>
<p>
    Net Transfer Amount (Insurer) = RM {{ number_format($net_transfer_amount_insurer, 2) }}
    Net Transfer Amount (Howden) = RM {{ number_format($net_transfer_amount, 2) }}
    Total Outstanding Amount = RM {{ number_format($total_outstanding, 2) }}
</p>

<table border="1">
    <tr>
        <th>Insurer</th>
        <th>Total Number of Transactions (#)</th>
        <th>Total Net Transfer Amount (RM)</th>
    </tr>
    @foreach ($details as $_details)
        <tr>
            @foreach ($_details as $value)
                <td>{{ $value }}</td>
            @endforeach
        </tr>
    @endforeach
</table>