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

    let body = document.querySelector('body');
    body.addEventListener('click', function (event) {
      if(event.target && event.target.matches('.twint')){
        self.onButtonClicked(event);
      }
    });
  }

  onButtonClicked(e){
    e.preventDefault();
    e.stopPropagation();

    console.log("clicked");

    ExpressCheckout.spinner.start();

    setTimeout(()=>{
      ExpressCheckout.spinner.stop();
      ExpressCheckout.modal.setContent(this.getRandomModalContent());
      ExpressCheckout.modal.show();
    }, 3000);
  }

  //TODO only for testing purposes
  getRandomModalContent() {
    // Generate a random 6-digit number as a string
    const randomId = Math.floor(100000 + Math.random() * 900000).toString();

    // Generate a random currency value between 0.1 and 100 with 2 decimal places
    const randomCurrencyValue = (Math.random() * 99.9 + 0.1).toFixed(2) + ' CHF';

    // Generate a random string for the pairing ID (e.g., 'pairing-123')
    const randomPairingId = 'pairing-' + Math.floor(Math.random() * 1000);

    // Randomize the boolean value (true/false)
    const randomBoolean = Math.random() < 0.5;

    return new ModalContent(randomId, randomCurrencyValue, randomPairingId, randomBoolean);
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
