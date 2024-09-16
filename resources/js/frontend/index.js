import {__} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';
import {useEffect} from "@wordpress/element";
import axios from "axios";

import TwintLogo from './../../../assets/images/twint_logo.png';
import IconScan from '../../../assets/images/icon-scan.svg';

const settings = getSetting('twint_regular_data', {});

const defaultLabel = __(
    'TWINT',
    'woocommerce-gateway-twint'
);

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
    /**
     * State for Check pairing...
     */
    const POLL_LIMIT = 500;
    const [checking, setChecking] = React.useState(false);
    const [countCheck, setCountCheck] = React.useState(0);
    const [startedAt, setStartedAt] = React.useState(null);
    const [timeOutId, setTimeOutId] = React.useState(null);

    /**
     * State for Pairing...
     */
    const [pairingId, setPairingId] = React.useState('');
    const [isExpress, setIsExpress] = React.useState(false);
    const [pairingToken, setPairingToken] = React.useState(null);
    const [qrCode, setQrCode] = React.useState('');
    const [interval, setInterval] = React.useState(0);
    const [shopName, setShopName] = React.useState('');
    const [adminUrl, setAdminUrl] = React.useState(woocommerce_params.ajax_url);
    const [nonce, setNonce] = React.useState(null);
    const [isOpenModal, setIsOpenModal] = React.useState(false);
    const [price, setPrice] = React.useState('');
    const [thankyouUrl, setThankyouUrl] = React.useState(null);

    const onCloseModal = () => {
        setIsOpenModal(false);
        clearTimeout(timeOutId);
        if (onClose) {
            onClose();
        }
    }

    const reachLimit = () => {
        if (checking || countCheck > POLL_LIMIT) {
            return true;
        }

        setCountCheck(countCheck + 1);
        setChecking(true);

        return false;
    }

    const getInterval = () => {
        if (startedAt === null) {
            setStartedAt(new Date());
        }

        let now = new Date();
        const seconds = Math.floor((now - startedAt) / 1000);

        let currentInterval = 2000; // Default to the first interval

        // express
        let stages = {
            0: 2000,
            600: 10000, //10 min
            3600: 0 // 1 hour
        }

        //regular
        if (!isExpress) {
            stages = {
                0: 2000,
                300: 10000, //5 min
                3600: 0 // 1 hour
            }
        }

        for (const [second, interval] of Object.entries(stages)) {
            if (seconds >= parseInt(second)) {
                currentInterval = interval;
            } else {
                break;
            }
        }

        return currentInterval;
    }

    const checkRegularCheckoutStatus = () => {
        if (nonce === null) {
            return;
        }

        if (reachLimit()) {
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
                    setStartedAt(null);
                    location.href = `${thankyouUrl}&twint_order_paid=true`;
                } else if (response.status === 'cancelled') {
                    location.href = `${thankyouUrl}&twint_order_cancelled=true`;
                } else if (response.isOrderPaid === false) {
                    setTimeout(checkRegularCheckoutStatus, 3000);
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
        checkRegularCheckoutStatus();
    }, [nonce, pairingId, startedAt]);

    return <>
        <aside role="dialog"
               className={`text-16 fixed inset-0 top-0 left-0 hidden items-center justify-center w-screen h-screen z-50 overflow-y-auto twint-modal ${isOpenModal ? '_show' : ''}`}
        >
            <div className="fixed inset-0 bg-black opacity-50"></div>
            <div className="modal-inner-wrap twint md:rounded-lg shadow-lg w-screen h-screen md:h-auto p-6 z-10 md:max-h-[95vh] overflow-y-auto">
                <header className="twint-modal-header sticky top-0 flex justify-between items-center bg-white md:rounded-t-lg py-2 px-4">
                    <button id="twint-close" className={`focus:border-none focus:outline-none`}
                            onClick={onCloseModal}
                            type="button">
                        <svg width="14" height="13" viewBox="0 0 14 13" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path
                                d="M1.40001 12.8078L0.692261 12.1L6.29226 6.50001L0.692261 0.900011L1.40001 0.192261L7.00001 5.79226L12.6 0.192261L13.3078 0.900011L7.70776 6.50001L13.3078 12.1L12.6 12.8078L7.00001 7.20776L1.40001 12.8078Z"
                                fill="#1C1B1F"></path>
                        </svg>
                        <span className={`ml-2`}>{__('Cancel checkout', 'woocommerce-gateway-twint')}</span>
                    </button>
                    <img className="twint-logo hidden md:block"
                         src={TwintLogo}
                         alt="TWINT Logo"/>
                </header>
                <div className="twint-modal-content twint-qr-container p-0 md:p-4" id="twint-qr-container">
                    <div id="qr-modal-content" className="text-20">
                        <input type="hidden" name="twint_wp_nonce" value={nonce} id="twint_wp_nonce"/>
                        <div className="flex flex-col md:flex-row gap-4 bg-gray-100">
                            <div
                                className="qr-code md:flex flex flex-1 order-1 md:order-none bg-white md:rounded-lg items-center justify-center">
                                <div className="md:flex flex flex-col text-center md:flex-col-reverse ">
                                    <div className="qr-token text-center my-3">
                                        <input id="qr-token"
                                               className="bg-white"
                                               type="text"
                                               value={pairingToken}
                                               disabled="disabled"
                                        />
                                    </div>

                                    <div className="md:hidden text-center my-4">
                                        <button id="btn-copy-token"
                                                data-clipboard-action="copy"
                                                data-clipboard-target="#qr-token"
                                                className="p-4 px-6 !bg-white rounded-lg border-black">
                                            {__('Copy code', 'woocommerce-gateway-twint')}
                                        </button>
                                    </div>

                                    <div className="hidden md:flex text-center items-center justify-center"
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
                                              className="text-center text-30 inline-block p-3 px-6 text-white bg-black font-semibold">
                                            {price}
                                        </span>
                                </div>
                                <div className="flex flex-1 bg-white p-4 md:rounded-lg items-center justify-center">
                                    {shopName}
                                </div>

                                <div className="app-selector md:hidden">
                                    {/* Android */}
                                    <div className="text-center mt-4 px-4">
                                        <a id="twint-addroid-button" className="no-underline block mb-1 bg-black text-white font-bold p-4 rounded-lg text-center hover:bg-gray-800 focus:outline-none focus:ring-gray-600 focus:ring-opacity-75
                            hover:text-white hover:no-underline
                        "
                                           data-href="javascript:window.location = intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code=--TOKEN--;S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end"
                                           href="javascript:window.location = 'intent://payment#Intent;action=ch.twint.action.TWINT_PAYMENT;scheme=twint;S.code=FUAY86ZVW;S.startingOrigin=EXTERNAL_WEB_BROWSER;S.browser_fallback_url=;end'">
                                            Switch to TWINT app now
                                        </a>
                                    </div>

                                    {/*Ios*/}
                                    <div id="twint-ios-container">
                                        <div className="my-6 text-center">
                                            Choose your TWINT app:
                                        </div>

                                        <div
                                          className="twint-app-container w-3/4 mx-auto justify-center max-w-screen-md mx-auto grid grid-cols-3 gap-4">
                                            <img
                                              src="https://twint-magento247.dev.nfq-asia.com/static/version1726028513/frontend/Magento/luma/en_US/Twint_Magento/images/apps/bank-bcv.png"
                                              className="shadow-2xl w-64 h-64 rounded-2xl mx-auto"
                                              data-link="twint-issuer5://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}"
                                              alt="BCV TWINT"/>
                                                <img
                                              src="https://twint-magento247.dev.nfq-asia.com/static/version1726028513/frontend/Magento/luma/en_US/Twint_Magento/images/apps/bank-cs.png"
                                              className="shadow-2xl w-64 h-64 rounded-2xl mx-auto"
                                              data-link="twint-issuer4://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}"
                                              alt="Credit Suisse TWINT"/>
                                                    <img
                                              src="https://twint-magento247.dev.nfq-asia.com/static/version1726028513/frontend/Magento/luma/en_US/Twint_Magento/images/apps/bank-pf.png"
                                              className="shadow-2xl w-64 h-64 rounded-2xl mx-auto"
                                              data-link="twint-issuer7://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}"
                                              alt="PostFinance TWINT"/>
                                                        <img
                                              src="https://twint-magento247.dev.nfq-asia.com/static/version1726028513/frontend/Magento/luma/en_US/Twint_Magento/images/apps/bank-raiffeisen.png"
                                              className="shadow-2xl w-64 h-64 rounded-2xl mx-auto"
                                              data-link="twint-issuer6://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}"
                                              alt="Raiffeisen TWINT" />
                                                            <img
                                              src="https://twint-magento247.dev.nfq-asia.com/static/version1726028513/frontend/Magento/luma/en_US/Twint_Magento/images/apps/bank-ubs.png"
                                              className="shadow-2xl w-64 h-64 rounded-2xl mx-auto"
                                              data-link="twint-issuer2://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}"
                                              alt="UBS TWINT" />
                                        </div>

                                        <select
                                          className="twint-select h-55 block my-4 w-full p-4 bg-white text-center appearance-none border-none focus:outline-none focus:ring-0">
                                            <option>Other banks</option>
                                            <option
                                              value="twint-issuer56://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">ABS
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer41://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">acrevis
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer25://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">AEK
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer30://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">AKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer42://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Alpha
                                                RHEINTAL Bank TWINT
                                            </option>
                                            <option
                                              value="twint-issuer12://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">AppKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer33://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Baloise
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer36://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BancaStato
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer40://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Bank
                                                Avera TWINT
                                            </option>
                                            <option
                                              value="twint-issuer48://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Bank
                                                BSU TWINT
                                            </option>
                                            <option
                                              value="twint-issuer52://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Bank
                                                EKI TWINT
                                            </option>
                                            <option
                                              value="twint-issuer53://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Bank
                                                Gantrisch TWINT
                                            </option>
                                            <option
                                              value="twint-issuer43://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Bank
                                                Thalwil TWINT
                                            </option>
                                            <option
                                              value="twint-issuer51://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Bank
                                                WIR TWINT
                                            </option>
                                            <option
                                              value="twint-issuer46://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Banque
                                                du Leman TWINT
                                            </option>
                                            <option
                                              value="twint-issuer16://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BCF
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer10://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BCGE
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer17://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BCJ
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer18://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BCN
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer13://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BCVs
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer23://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BEKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer44://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Bernerland
                                                Bank TWINT
                                            </option>
                                            <option
                                              value="twint-issuer26://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BLKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer49://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">BSD
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer37://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">CA
                                                next bank TWINT
                                            </option>
                                            <option
                                              value="twint-issuer45://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Coop
                                                Finance+ TWINT
                                            </option>
                                            <option
                                              value="twint-issuer15://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">GKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer22://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">GLKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer47://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">GRB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer38://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">LLB
                                                Schweiz TWINT
                                            </option>
                                            <option
                                              value="twint-issuer32://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">LUKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer21://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Migros
                                                Bank TWINT
                                            </option>
                                            <option
                                              value="twint-issuer29://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">NKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer8://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">OKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer28://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Radicant
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer55://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Regiobank
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer57://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Saanen
                                                Bank TWINT
                                            </option>
                                            <option
                                              value="twint-issuer14://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">SGKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer24://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">SHKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer54://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">SLF
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer27://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Swissquote
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer31://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">SZKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer19://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">TKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer1://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">TWINT
                                                – andere Banken &amp; Prepaid
                                            </option>
                                            <option
                                              value="twint-issuer34://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">UKB
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer20://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">Valiant
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer39://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">VZ
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer35://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">yuh
                                                TWINT
                                            </option>
                                            <option
                                              value="twint-issuer9://applinks/?al_applink_data={&quot;app_action_type&quot;:&quot;TWINT_PAYMENT&quot;,&quot;extras&quot;: {&quot;code&quot;: &quot;--TOKEN--&quot;,},&quot;referer_app_link&quot;: {&quot;target_url&quot;: &quot;&quot;, &quot;url&quot;: &quot;&quot;, &quot;app_name&quot;: &quot;EXTERNAL_WEB_BROWSER&quot;}, &quot;version&quot;: &quot;6.0&quot;}">ZugerKB
                                                TWINT
                                            </option>
                                        </select>
                                    </div>
                                    <div className="qr-code text-center md:hidden">
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

                        <div className="qr-code container mx-auto mt-4 text-16 p-4">
                            <div className="grid grid-cols-1">
                                <div className="hidden md:flex flex-col items-center">
                                    <div className="flex justify-center">
                                        <img className="w-55 h-55"
                                             src={IconScan}
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
