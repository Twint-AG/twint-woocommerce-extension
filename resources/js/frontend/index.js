import {__} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';
import {useEffect, useState} from "@wordpress/element";
import axios from "axios";

const settings = getSetting('twint_regular_data', {});

const defaultLabel = __(
    'TWINT',
    'woo-gutenberg-products-block'
);

console.log(settings);
const label = decodeEntities(settings.title) || defaultLabel;

/**
 * See https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md#payment-methods---registerpaymentmethod-options-
 * @param onClick
 * @param onClose
 * @param onSubmit
 * @param onError
 * @param eventRegistration
 * @param emitResponse
 * @param activePaymentMethod
 * @param shippingData
 * @param isEditing
 * @returns {JSX.Element|string}
 * @constructor
 */
const ModalTwintPayment = (
    {
        onClick,
        onClose,
        onSubmit,
        onError,
        eventRegistration,
        emitResponse,
        activePaymentMethod,
        shippingData,
        isEditing,
    }) => {
    const [pairingId, setPairingId] = React.useState('');
    const [pairingToken, setPairingToken] = React.useState(null);
    const [qrCode, setQrCode] = React.useState('');
    const [interval, setInterval] = React.useState(3); // seconds
    const [shopName, setShopName] = React.useState('');
    const [adminUrl, setAdminUrl] = React.useState(woocommerce_params.ajax_url);
    const [nonce, setNonce] = React.useState(null);
    const [isOpenModal, setIsOpenModal] = React.useState(false);
    const [price, setPrice] = React.useState('');
    const [thankyouUrl, setThankyouUrl] = React.useState(null);

    const onCloseModal = () => {
        setIsOpenModal(false);
        if (onClose) {
            onClose();
        }
    }

    const checkOrderRegularStatus = () => {
        if (nonce === null) {
            return;
        }

        if (pairingId !== null) {
            let formData = new FormData();
            formData.append('action', 'twint_check_pairing_status');
            formData.append('pairingId', pairingId);
            formData.append('nonce', nonce);
            axios.post(adminUrl, formData).then(response => {
                response = response.data;
                console.log(response);
                if (response.success === true && response.isOrderPaid === true) {
                    location.href = `${thankyouUrl}&twint_order_paid=true`;
                } else if (response.status === 'cancelled') {
                    location.href = `${thankyouUrl}&twint_order_cancelled=true`;
                } else if (response.isOrderPaid === false) {
                    setTimeout(checkOrderRegularStatus, interval * 1000);
                }
            }).catch(error => {
                console.log(error);
            });
        }
    }

    const {onCheckoutAfterProcessingWithSuccess} = eventRegistration;
    useEffect(() => {
        const unsubscribe = onCheckoutAfterProcessingWithSuccess(async ({processingResponse}) => {
            const paymentDetails = processingResponse.paymentDetails || {};
            console.log(paymentDetails);
            if (paymentDetails.result === 'success') {
                setIsOpenModal(true);
                setPairingId(paymentDetails['pairingId']);
                setPairingToken(paymentDetails['pairingToken']);
                setQrCode(paymentDetails['qrcode']);
                setNonce(paymentDetails['nonce']);
                setThankyouUrl(paymentDetails['thankyouUrl']);
                setShopName(paymentDetails['shopName']);

                const priceFormat = `${paymentDetails['currency']} ${paymentDetails['amount']}`;
                setPrice(priceFormat);
            }
        });

        return () => unsubscribe();
    }, [
        emitResponse.noticeContexts.PAYMENTS,
        emitResponse.responseTypes.ERROR,
        emitResponse.responseTypes.SUCCESS,
    ]);

    useEffect(() => {
        checkOrderRegularStatus();
    }, [nonce, pairingId]);

    return <>
        <aside role="dialog"
               className={`text-16 modal-popup twint-modal-slide modal-slide _inner-scroll ${isOpenModal ? '_show' : ''}`}
        >
            <div className="modal-inner-wrap twint">
                <header className="twint-modal-header sticky top-0 flex justify-between items-center bg-white">
                    <button id="twint-close"
                            onClick={onCloseModal}
                            type="button">
                        <svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M1.40001 12.8078L0.692261 12.1L6.29226 6.50001L0.692261 0.900011L1.40001 0.192261L7.00001 5.79226L12.6 0.192261L13.3078 0.900011L7.70776 6.50001L13.3078 12.1L12.6 12.8078L7.00001 7.20776L1.40001 12.8078Z"
                                fill="#1C1B1F"></path>
                        </svg>
                        <span>{__('Cancel checkout', 'woocommerce-gateway-twint')}</span>
                    </button>
                    <img className="twint-logo hidden md:block mr-4"
                         src="http://localhost/wp-content/plugins/woocommerce-gateway-twint/assets/images/twint_logo.png"
                         alt="TWINT Logo"/>
                </header>
                <div className="twint-modal-content twint-qr-container p-0 md:p-4" id="twint-qr-container">
                    <div id="qr-modal-content"
                         className="text-20"
                         data-mobile=""
                         data-is-android-device=""
                         data-is-ios-device=""
                         data-android-link="Warning: Undefined array key "
                         data-pairing-id={pairingId}>
                        <input type="hidden" name="twint_wp_nonce" value={nonce} id="twint_wp_nonce"/>
                        <div className="flex flex-col md:flex-row gap-4 bg-gray-100">
                            <div
                                className="qr-code d-none d-lg-block md:flex flex flex-1 order-1 md:order-none bg-white md:rounded-lg items-center justify-center">
                                <div data-twint-copy-token=""
                                     className="md:flex flex flex-col text-center md:flex-col-reverse ">
                                    <div className="qr-token text-center my-4 md:mt-6">
                                        <input id="qr-token"
                                               className="bg-white"
                                               type="text"
                                               value={pairingToken}
                                               disabled="disabled"
                                        />
                                    </div>

                                    <div className="md:hidden text-center mt-4">
                                        <button id="btn-copy-token"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#qr-token"
                                                className="p-4 px-6 !bg-white rounded-lg border-black">
                                            {__('Copy code', 'woocommerce-gateway-twint')}
                                        </button>
                                    </div>

                                    <div className="flex text-center items-center justify-center"
                                         id="qrcode"
                                         title={pairingToken}>
                                        <img
                                            src={qrCode}
                                            alt={__('QR Code', 'woocommerce-gateway-twint')}/>
                                    </div>
                                </div>
                            </div>

                            <div className="flex-1 order-0 md:order-1 flex flex-col gap-1 md:gap-4">
                                <div className="flex flex-1 bg-white p-4 md:rounded-lg items-center justify-center">
                                        <span id="twint-amount"
                                              className="text-center text-35 inline-block p-4 px-6 text-white bg-black font-semibold">
                                            {price}
                                        </span>
                                </div>
                                <div className="flex flex-1 bg-white p-4 md:rounded-lg items-center justify-center">
                                    {shopName}
                                </div>

                                <div className="app-selector md:hidden">
                                    <div className="qr-code d-none md:flex text-center md:hidden">
                                        <div className="flex items-center justify-center mx-4">
                                            <div
                                                className="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                            <span className="mx-4 text-black">
                                                {__('or', 'woocommerce-gateway-twint')}
                                            </span>
                                            <div
                                                className="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                                        </div>

                                        <div className="row qr-code my-3">
                                            <div className="col-9 text-center">
                                                {__('Enter this code in your TWINT app:', 'woocommerce-gateway-twint')}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="qr-code d-none d-lg-block container mx-auto mt-4 text-16">
                            <div className="grid grid-cols-1">
                                <div className="flex flex-col items-center p-4">
                                    <div className="flex justify-center">
                                        <img className="w-55 h-55"
                                             src="http://localhost/wp-content/plugins/woocommerce-gateway-twint/assets/images/icon-scan.svg"
                                             alt="scan"/>
                                    </div>
                                    <div className="text-center mt-4">
                                        {__('Scan this QR Code with your TWINT app to complete the checkout.', 'woocommerce-gateway-twint')}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div data-role="focusable-end" tabIndex="0"></div>
            </div>
        </aside>
    </>;
    return decodeEntities(settings.description || '');
};

const BlockEditorTwintComponent = () => {
    return (<div onClick={(data, actions) => {
        console.log(data, actions);
        return false;
    }}></div>);
}

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = (props) => {
    const {PaymentMethodLabel} = props.components;
    return <PaymentMethodLabel text={label}/>;
};

/**
 * Twint payment method config object.
 */
const Twint = {
    name: "twint_regular",
    label: <Label/>,
    content: <ModalTwintPayment/>,
    edit: <BlockEditorTwintComponent/>,
    canMakePayment: () => true,
    ariaLabel: label,
    placeOrderButtonLabel: __('Proceed to Twint', 'woocommerce-gateway-twint'),
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(Twint);
