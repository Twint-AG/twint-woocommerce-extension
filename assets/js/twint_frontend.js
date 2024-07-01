let urlParams = new URLSearchParams(window.location.search);
jQuery(document).ready(function ($) {
    if (!urlParams.get('twint_order_paid') && !urlParams.get('twint_order_cancelled') && urlParams.get('order-received')) {
        const checkOrderStatusInterval = setInterval(checkOrderStatusHandler, 5000)
        console.log(checkOrderStatusInterval);
    }
});

function checkOrderStatusHandler() {
    if (!urlParams.get('twint_order_paid') && urlParams.get('order-received')) {
        // TODO Check Status of the order
        const nonce = jQuery('input[name="twint_wp_nonce"]').val();

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
                        window.location.href = `${currentURL}&twint_order_cancelled=true`;                    }
                }
            }
        }, 5000);
    }
}