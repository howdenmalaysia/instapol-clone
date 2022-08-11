require('./bootstrap');

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

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
    
});