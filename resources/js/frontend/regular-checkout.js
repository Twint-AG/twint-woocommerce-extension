import {__} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {getSetting} from '@woocommerce/settings';
import {useEffect} from "@wordpress/element";

import Modal from './express/modal/modal';
import ModalContent from "./express/modal/content";

const settings = getSetting('twint_regular_data', {});

const label = __('TWINT', 'woocommerce-gateway-twint');

/**
 * See https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md#payment-methods---registerpaymentmethod-options- * @param eventRegistration
 * @param eventRegistration
 * @param emitResponse
 * @returns {JSX.Element}
 * @constructor
 */
const ModalTwintPayment = ({eventRegistration, emitResponse}) => {
  const {onCheckoutAfterProcessingWithSuccess} = eventRegistration;
  useEffect(() => {
    const unsubscribe = onCheckoutAfterProcessingWithSuccess(async ({processingResponse}) => {
      const details = processingResponse.paymentDetails;
      if (details.result === 'success') {
        let modal = new Modal();
        modal.addCallback(Modal.EVENT_CLOSED, ()=> {
          location.reload();
        });
        modal.setContent(new ModalContent(details.pairingToken,
          details.amount + details.currency,
          details.pairingId,
          false));
        modal.show();
      }
    });

    return () => unsubscribe();
  }, [
    emitResponse.noticeContexts.PAYMENTS,
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
  ]);

  return <></>;
};

const BlockEditorTwintComponent = () => {
  return (<div onClick={(data, actions) => {
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
 * Twint Regular payment method config object.
 */
const TwintRegular = {
  name: "twint_regular",
  label: <Label/>,
  content: <ModalTwintPayment/>,
  edit: <BlockEditorTwintComponent/>,
  canMakePayment: () => true,
  ariaLabel: label,
  supports: {
    features: settings.supports,
  },
};

registerPaymentMethod(TwintRegular);
