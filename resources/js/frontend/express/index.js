import Modal from './modal/modal';
import ButtonHandler from './button/button-handler';
import ModalContent from './modal/content';
import Action from './checkout/action';
import ContextFactory from './context/factory';

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
    if(data.openMiniCart){
      jQuery('body').trigger('wc_fragment_refresh');
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
      ExpressCheckout.modal = new Modal();
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  let handler = new ExpressCheckout();
  handler.handle();
});
