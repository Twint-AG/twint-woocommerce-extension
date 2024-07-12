import {sprintf, __} from '@wordpress/i18n';
import {registerPaymentMethod} from '@woocommerce/blocks-registry';
import {decodeEntities} from '@wordpress/html-entities';
import {getSetting} from '@woocommerce/settings';

const settings = getSetting('twint_regular_data', {});

const defaultLabel = __(
    'Twint Payment',
    'woo-gutenberg-products-block'
);

console.log(settings);
const label = decodeEntities(settings.title) || defaultLabel;
/**
 * Content component
 */
const Content = () => {
    return decodeEntities(settings.description || '');
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
 * Twint payment method config object.
 */
const Twint = {
    name: "twint_regular",
    label: <Label/>,
    content: <Content/>,
    edit: <Content/>,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};

registerPaymentMethod(Twint);
