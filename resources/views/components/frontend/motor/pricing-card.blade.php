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
                <td id="road-tax" class="text-end">{{ $roadtaxTotal }}</td>
            </tr>
            @if ($promo)
                <tr>
                    <td colspan="3">
                        <div class="input-group">
                            <input type="text" id="promo-code" class="form-control rounded-start" placeholder="{{ __('frontend.motor.payment_summary_page.insert_promo_code') }}">
                            <div class="input-group-append">
                                <button id="check-promo" class="btn btn-primary text-white rounded-end">{{ __('frontend.button.check') }}</button>
                            </div>
                        </div>
                        <span id="promo-error" class="text-danger fw-bold d-none"></span>
                    </td>
                </tr>
            @else
                <tr>
                    <td class="border-bottom border-5" colspan="3"></td>
                </tr>
            @endif
            <tr>
                <td class="fw-bold text-uppercase">{{ __('frontend.price_card.total_payable') }}</td>
                <td class="fw-bold text-end">RM</td>
                <td id="total-payable" class="fw-bold text-end">{{ $totalPayable }}</td>
            </tr>
        </tbody>
    </table>
</div>

@push('after-scripts')
    <script>
        $(() => {
            $('#check-promo').on('click', (e) => {
                if(!$('#promo-error').hasClass('d-none')) {
                    $('#promo-error').toggleClass('d-none');
                }

                if($('#promo-code').val() == '') {
                    $('#promo-error').text({{ __('frontend.motor.payment_summary_page.promo_code_empty') }}).removeClass('d-none');
                    return;
                }

                $('#basic-premium').toggleClass('loadingButton');
                $('#gross-premium').toggleClass('loadingButton');
                $('#sst').toggleClass('loadingButton');
                $('#road-tax').toggleClass('loadingButton');
                $('#total-payable').toggleClass('loadingButton');

                $(e.target).toggleClass('loadingButton');

                checkPromo();
            });

            function checkPromo() {
                instapol.post("{{ route('motor.api.use-promo') }}", {
                    motor: motor,
                    code: $('#promo-code').val()
                }).then((res) => {
                    console.log(res);

                    $('#motor').val(JSON.stringify(res.data));

                    // Update Pricing Card
                    $('#basic-premium').text(formatMoney(res.data.premium.basic_premium)).removeClass('toggleButton');
                    $('#gross-premium').text(formatMoney(res.data.premium.gross_premium)).removeClass('toggleButton');
                    $('#sst').text(formatMoney(res.data.premium.sst_amount)).removeClass('toggleButton');
                    $('#road-tax').text(formatMoney(res.data.roadtax.total)).removeClass('toggleButton');
                    $('#total-payable').text(formatMoney(res.data.premium.total_payable + res.data.roadtax.total)).removeClass('toggleButton');

                    $(e.target).removeClass('loadingButton');
                }).catch((err) => {
                    $(e.target).removeClass('loadingButton');
                    $('#basic-premium').removeClass('loadingButton');
                    $('#gross-premium').removeClass('loadingButton');
                    $('#sst').removeClass('loadingButton');
                    $('#road-tax').removeClass('loadingButton');
                    $('#total-payable').removeClass('loadingButton');

                    swalAlert(err.message);
                    console.log(err);
                });
            }
        });
    </script>
@endpush