import axios from 'axios';

document.addEventListener("DOMContentLoaded", function (event) {
    class PaymentStatusRefresh {
        count = 0;

        constructor() {
            this.options = {
                adminUrl: woocommerce_params.ajax_url,
                urlParams: new URLSearchParams(window.location.search),
                nonceValue: null,
                containerSelector: '#twint-qr-container',
                pairingHash: null,
                interval: 5, // seconds
                expressCheckout: false,
                orderId: -1,
            };
        }

        init() {
            this.checking = false;
            this.$container = document.querySelector(this.options.containerSelector);

            if (!this.$container) {
                return;
            }

            this.options.nonceValue = document.querySelector('input#twint_wp_nonce').value;
            this.options.orderId = this.$container.getAttribute('data-order-id');

            if (!this.options.expressCheckout) {
                this.checkOrderRegularStatus();
            }
        }

        reachLimit() {
            if (this.checking || this.count > 10) {
                return true;
            }

            this.count++;
            this.checking = true;

            return false;
        }

        checkOrderRegularStatus() {
            const nonce = this.options.nonceValue;
            if (nonce === '' || nonce === undefined) {
                return;
            }

            // TODO Implement check that this call reaches to the limit or not

            if (this.options.orderId !== -1) {
                let formData = new FormData();
                formData.append('action', 'twint_check_order_status');
                formData.append('orderId', parseInt(this.options.orderId));
                formData.append('nonce', this.options.nonceValue);
                axios.post(this.options.adminUrl, formData).then(response => {
                    response = response.data;
                    console.log(response);
                    if (response.success === true && response.isOrderPaid === true) {
                        let currentURL = window.location.href;
                        window.location.href = `${currentURL}&twint_order_paid=true`;
                    } else if (response.status === 'cancelled') {
                        let currentURL = window.location.href;
                        window.location.href = `${currentURL}&twint_order_cancelled=true`;
                    } else if (response.isOrderPaid === false) {
                        setInterval(this.checkOrderRegularStatus.bind(this), this.options.interval * 1000);
                    }
                }).catch(error => {
                    console.log(error);
                })
            }
        }
    }

    new PaymentStatusRefresh().init();
});