require('./bootstrap');

import Alpine from 'alpinejs';
import AOS from 'aos';
var Inputmask = require('inputmask');

window.Alpine = Alpine;
Alpine.start();

AOS.init();

var moneyFormatter = new Intl.NumberFormat('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

window.formatDate = function (date, format = process.env.MIX_DATE_FORMAT) {
    return moment(date, [process.env.MIX_DATE_FORMAT, 'YYYY-MM-DD']).format(format);
}

window.formatMoney = function (number) {
    return moneyFormatter.format(number);
}

$(function() {
    $(document).on('scroll', function () {
        var nav = $('.navbar-fixed-top');
        nav.addClass('fixed-top', $(this).scrollTop() > nav.height());
    });

    $('input.uppercase').on('keyup change', function() {
        $(this).val($(this).val().toUpperCase());
    });
});