require('./bootstrap');

import Alpine from 'alpinejs';
import AOS from 'aos';
import { Select2 } from 'select2';
import Swal from 'sweetalert2';
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

window.swalLoading = function () {
    Swal.fire({
        title: 'We appreciate your patience!',
        color: '#9a5cd0',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

window.swalAlert = function (message, callback = null, showDenyButton = false, icon = 'error', confirmButtonText = 'Go Back') {
    var title = '';
    switch(icon) {
        case 'success': {
            title = 'Yay!';
            break;
        }
        case 'warning': {
            title = 'Hmm...';
            break;
        }
        case 'error': {
            title = 'Oops...';
            break;
        }
    }

    Swal.fire({
        title: title,
        icon: icon,
        text: message,
        showDenyButton: showDenyButton,
        allowOutsideClick: false,
        allowEscapeKey: false,
        confirmButtonText: confirmButtonText
    }).then(callback);
}

window.swalHide = function () {
    Swal.close();
}

$(function() {
    $(document).on('scroll', function () {
        var nav = $('.navbar-fixed-top');

        if($(this).scrollTop() > nav.height()) {
            nav.addClass('fixed-top');
        } else {
            if(nav.hasClass('fixed-top')) {
                nav.removeClass('fixed-top');
            }
        }
    });

    $('input.uppercase').on('keyup change', function() {
        $(this).val($(this).val().toUpperCase());
    });

    $('[data-select]').select2({
        width: '100%',
        theme: 'bootstrap-5'
    }).on('select2:select', function () {
        $(this).parsley().validate();
    });

    $('[data-bs-toggle=tooltip]').each((index, element) => {
        new bootstrap.Tooltip(element);
    });
});