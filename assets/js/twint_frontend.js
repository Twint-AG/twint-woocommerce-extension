let urlParams = new URLSearchParams(window.location.search);
jQuery(document).ready(function ($) {
    const nonce = jQuery('input[name="twint_wp_nonce"]').val();
    if (nonce !== undefined && nonce !== '') {
        if (!urlParams.get('twint_order_paid') && !urlParams.get('twint_order_cancelled') && urlParams.get('order-received')) {
            const checkOrderStatusInterval = setInterval(checkOrderStatusHandler, 5000)
            console.log(checkOrderStatusInterval);
        }
    }

    const $twintContainer = jQuery('#twint-qr-container');

    const isAndroid = $twintContainer.attr('data-is-android-device');
    const isIos = $twintContainer.attr('data-is-ios-device');

    if (isAndroid) {
        let link = $twintContainer.attr('data-android-link');
        openAppBank(link);
    }

    if (isIos) {
        console.log('IOS');
    }

    $(document).on('click', '.bank-logo', function (evt) {
        evt.preventDefault();
        const $this = $(this);
        const link = $this.attr('data-link');

        openAppBank(link);
    });
});

function showMobileQrCode() {
    jQuery('.qr-code').removeClass('d-none');
}

function openAppBank(link) {
    if (link) {
        try {
            window.location.replace(link);

            const checkLocation = setInterval(() => {
                if (window.location.href !== link) {
                    showMobileQrCode();
                }
                clearInterval(checkLocation);
            }, 2 * 1000);
        } catch (e) {
            this.showMobileQrCode();
        }
    }
}

function checkOrderStatusHandler() {
    if (!urlParams.get('twint_order_paid') && urlParams.get('order-received')) {
        // TODO Check Status of the order
        const nonce = jQuery('input[name="twint_wp_nonce"]').val();
        if (nonce === undefined || nonce === '') {
            return;
        }

        jQuery.ajax({
            url: woocommerce_params.ajax_url,
            data: {
                action: 'twint_check_order_status',
                orderId: parseInt(urlParams.get('order-received')),
                nonce: nonce,
            },
            success: function (response) {
                response = JSON.parse(response);
                if (response.success === true && response.isOrderPaid === true) {
                    let currentURL = window.location.href;
                    window.location.href = `${currentURL}&twint_order_paid=true`;
                } else {
                    console.log(response);
                    if (response.status === 'cancelled') {
                        let currentURL = window.location.href;
                        window.location.href = `${currentURL}&twint_order_cancelled=true`;
                    }
                }
            }
        }, 5000);
    }
}