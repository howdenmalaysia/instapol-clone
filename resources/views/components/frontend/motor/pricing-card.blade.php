<div class="border-0 card sticky-top">
    <table id="pricing-table" class="table table-borderless">
        <thead>
            <tr class="align-middle">
                <td class="w-50 mt-1" colspan="3">
                    <img src="{{ $insurerLogo }}" alt="{{ $insurerName }}" class="img-fluid" />
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ __('frontend.price_card.basic_premium') }}
                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.price_card.tooltip.basic_premium') }}">
                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                    </span>
                </td>
                <td class="text-end">RM</td>
                <td id="basic-premium" class="text-end">{{ $basicPremium }}</td>
            </tr>
            <tr>
                <td>
                    {{ '- ' . __('frontend.price_card.ncd') }}
                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.price_card.tooltip.ncd') }}">
                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                    </span>
                </td>
                <td class="text-end">RM</td>
                <td id="ncd" class="text-end">{{ $ncdAmount }}</td>
            </tr>
            <tr>
                <td>
                    {{ '+ ' . __('frontend.price_card.additional_coverage') }}
                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.price_card.tooltip.additional_coverage') }}">
                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                    </span>
                </td>
                <td class="text-end">RM</td>
                <td id="add-ons-premium" class="text-end">{{ $totalBenefitAmount }}</td>
            </tr>
            <tr>
                <td class="border-bottom border-5" colspan="3"></td>
            </tr>
            <tr>
                <td>
                    {{ __('frontend.price_card.gross_premium') }}
                    <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ __('frontend.price_card.tooltip.gross_premium') }}">
                        <i class="fa-solid fa-circle-question text-primary fa-15x"></i>
                    </span>
                </td>
                <td class="text-end">RM</td>
                <td id="gross-premium" class="text-end">{{ $grossPremium }}</td>
            </tr>
            <tr>
                <td>{{ '+ ' . __('frontend.price_card.service_tax') }}</td>
                <td class="text-end">RM</td>
                <td id="sst" class="text-end">{{ $sstAmount }}</td>
            </tr>
            <tr>
                <td>{{ '+ ' . __('frontend.price_card.stamp_duty') }}</td>
                <td class="text-end">RM</td>
                <td id="stamp-duty" class="text-end">{{ $stampDuty }}</td>
            </tr>
            <tr>
                <td>{{ '+ ' . __('frontend.price_card.road_tax') }}</td>
                <td class="text-end">RM</td>
                <td id="road-tax" class="text-end">0.00</td>
            </tr>
            <tr>
                <td class="border-bottom border-5" colspan="3"></td>
            </tr>
            <tr>
                <td class="fw-bold text-uppercase">{{ __('frontend.price_card.total_payable') }}</td>
                <td class="fw-bold text-end">RM</td>
                <td id="total-payable" class="fw-bold text-end">{{ $totalPayable }}</td>
            </tr>
        </tbody>
    </table>
</div>