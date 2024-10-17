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
    ExpressCheckout.modal.refreshMiniCart();

    if (data.openMiniCart) {
      return this.showMessageAndOpenMiniCart();
    }
    if (!data.success) {
      let messages = document.querySelector('.woocommerce-notices-wrapper');
      const {message} = data;
      if (messages) {
        messages.innerHTML =
          `<div class="woocommerce-info woocommerce-message is-success" role="alert">              
            <div class="wc-block-components-notice-banner__content">
              ` + message + `
            </div>
          </div>`;
      }
      return;
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
      ExpressCheckout.modal = new Modal(Modal.TYPE_EXPRESS_CHECKOUT);
    }
  }

  showMessageAndOpenMiniCart() {
    let messages = document.querySelector('.woocommerce-notices-wrapper');
    if (messages) {
      let data = ExpressCheckout.modal.getData();
      messages.innerHTML =
        `<div class="woocommerce-info woocommerce-message is-success" role="alert">              
            <div class="wc-block-components-notice-banner__content">
              <a href="/cart/" tabindex="1" class="button wc-forward wp-element-button">` + data.label + `</a>
              ` + data.message + `
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
