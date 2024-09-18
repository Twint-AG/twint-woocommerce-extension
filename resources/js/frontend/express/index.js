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
    ExpressCheckout.modal.setContent(this.getRandomModalContent());
    ExpressCheckout.modal.show();
  }

  onFailureCallback() {
    console.log("Cannot perform express checkout");
  }

  //TODO only for testing purposes
  getRandomModalContent() {
    // Generate a random 6-digit number as a string
    const randomId = Math.floor(100000 + Math.random() * 900000).toString();

    // Generate a random currency value between 0.1 and 100 with 2 decimal places
    const randomCurrencyValue = (Math.random() * 99.9 + 0.1).toFixed(2) + ' CHF';

    // Generate a random string for the pairing ID (e.g., 'pairing-123')
    const randomPairingId = 'pairing-' + Math.floor(Math.random() * 1000);

    return new ModalContent(randomId, randomCurrencyValue, randomPairingId, true);
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
