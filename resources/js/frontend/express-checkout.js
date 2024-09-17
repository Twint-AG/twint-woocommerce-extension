import {__} from '@wordpress/i18n';
import {registerExpressPaymentMethod} from '@woocommerce/blocks-registry';

const label = __('TWINT', 'woocommerce-gateway-twint');

/**
 *
 * @returns {JSX.Element}
 * @constructor
 */
const TwintExpressCheckout = () => {

  return <button className={twint - button}>Twint</button>;
};

const TwintExpressCheckoutEdit = () => {

  return <></>;
};

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
const TwintExpress = {
  name: "twint_express",
  label: <Label/>,
  content: <TwintExpressCheckout/>,
  edit: <TwintExpressCheckoutEdit/>,
  canMakePayment: () => true,
  ariaLabel: label,
  placeOrderButtonLabel: __('Proceed to TWINT', 'woocommerce-gateway-twint'),
  supports: {
    features: [],
  },
};

// registerExpressPaymentMethod(TwintExpress);

