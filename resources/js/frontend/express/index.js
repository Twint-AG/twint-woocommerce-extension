import Spinner from './spinner/index';
import Modal from './modal/modal';
import ButtonHandler from './button/button-handler';
import ModalContent from './modal/content';

class ExpressCheckout {
  // Singleton instance
  static modal;
  static spinner;

  constructor() {
    this.buttonManager = new ButtonHandler();
  }

  handle(){
    this.init();
    this.registerEvents();
  }

  registerEvents() {
    let self = this;

    this.buttonManager.buttons.forEach(function (button) {
      button.addEventListener('click', self.onButtonClicked.bind(self));
    })
  }

  onButtonClicked(e){
    e.preventDefault();
    e.stopPropagation();

    ExpressCheckout.modal.setContent(new ModalContent('123456', '1.0 CHF', 'pairing-id',true));
    ExpressCheckout.modal.show();
  }

  init() {
    if(!ExpressCheckout.modal){
      ExpressCheckout.modal = new Modal();
    }

    if(!ExpressCheckout.spinner){
      ExpressCheckout.spinner = new Spinner();
    }
  }
}

document.addEventListener( 'DOMContentLoaded', () => {
  let handler = new ExpressCheckout();
  handler.handle();
});
