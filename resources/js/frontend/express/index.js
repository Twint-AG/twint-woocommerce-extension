import Modal from './modal/modal';
import ButtonHandler from './button/button-handler';
import ModalContent from './modal/content';
import Action from './checkout/action';
import ContextFactory from './context/factory';
import {__} from "@wordpress/i18n";

class ExpressCheckout {
  // Singleton instance
  static modal;

  constructor() {
    this.buttonManager = new ButtonHandler();
    this.checkoutAction = new Action();
  }

  handle() {
    this.init();
    this.registerEvents();
  }

  registerEvents() {
    let self = this;

    this.buttonManager.buttons.forEach(function (button) {
      button.addEventListener('click', self.onButtonClicked.bind(self));
    })

    let body = document.querySelector('body');
    body.addEventListener('click', function (event) {
      if (event.target && event.target.matches('.twint')) {
        self.onButtonClicked(event);
      }
    });
  }

  onButtonClicked(e) {
    e.preventDefault();
    e.stopPropagation();

    const button = e.target.closest('.twint-button');

    this.checkoutAction.handle(ContextFactory.getContext(button), this.onSuccessCallback.bind(this), this.onFailureCallback.bind(this));
  }

  onSuccessCallback(data) {
    if (data.openMiniCart) {
      ExpressCheckout.modal.refreshMiniCart();
      return this.showMessageAndOpenMiniCart();
    }

    ExpressCheckout.modal.setContent(
      new ModalContent(data.token, data.amount, data.pairing, true)
    );
    ExpressCheckout.modal.show();
  }

  onFailureCallback() {
    console.log("Cannot perform express checkout");
  }

  init() {
    if (!ExpressCheckout.modal) {
      ExpressCheckout.modal = new Modal();
    }
  }

  showMessageAndOpenMiniCart(){
    let messages = document.querySelector('.woocommerce-notices-wrapper');
    if (messages) {
      messages.innerHTML =
        `<div class="wc-block-components-notice-banner is-success" role="alert">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true"
                   focusable="false">
                <path d="M16.7 7.1l-6.3 8.5-3.3-2.5-.9 1.2 4.5 3.4L17.9 8z"></path>
              </svg>
              <div class="wc-block-components-notice-banner__content">
                <a href="/cart/" tabIndex="1" class="button wc-forward wp-element-button">` + __('View cart', 'woocommerce') + `</a>
                ` + __("You have existing products in the shopping cart. Please review your shopping cart before continue.", 'woocommerce-gateway-twint') + `
              </div>
            </div>`;

      messages.scrollIntoView({
        behavior: 'smooth', // Enables smooth scrolling
        block: 'start'      // Aligns the element to the top of the viewport
      });
    }

    jQuery('.wc-block-mini-cart__button ').click();
  }
}

document.addEventListener('DOMContentLoaded', () => {
  let handler = new ExpressCheckout();
  handler.handle();
});
