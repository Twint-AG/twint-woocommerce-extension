import { registerBlockType } from '@wordpress/blocks';

registerBlockType('twint/mini-cart-express-checkout-button', {
  title: 'TWINT Mini Cart Express Checkout Button',
  icon: 'cart',
  category: 'widgets',
  edit: function(props) {
    return <button className={`twint-button`}>ABC</button>
  },
  save: function() {
    return null; // We're using a dynamic block, so we return null here
  }
});
