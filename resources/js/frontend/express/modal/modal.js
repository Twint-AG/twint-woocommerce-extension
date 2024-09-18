import StatusRefresher from "./handler/status-refresher";
import TokenCopier from "./handler/token-copier";
import AndroidConnector from "./connector/android-connector";
import IosConnector from "./connector/ios-connector";

class Modal {
  constructor(){
    this.element = document.getElementById('twint-modal');
    this.closeBtn = this.element.querySelector('#twint-close');

    // Handlers
    this.statusRefresher = new StatusRefresher();
    this.tokenCopier = new TokenCopier();

    this.connectors = [];
    this.connectors.push(new AndroidConnector());
    this.connectors.push(new IosConnector());

    this.registerEvents();
  }

  setContent(content){
    this.content = content;
  }

  show(){
    // Display
    let span = this.closeBtn.querySelector('span');
    span.innerHTML = this.closeBtn.getAttribute('data-default');

    this.tokenCopier.reset();

    // prepare modal content
    this.content.render();

    //Show modal
    this.element.classList.remove('!hidden');

    //Connector
    this.connectors.forEach(connector => {
      connector.setToken(this.content.token);
      connector.init();
    });

    //Events
    this.statusRefresher.setPairing(this.content.pairing);
    this.statusRefresher.addCallBack(StatusRefresher.EVENT_CANCELLED, this.close.bind(this));
    this.statusRefresher.addCallBack(StatusRefresher.EVENT_PAID, this.onPaid.bind(this));
    this.statusRefresher.start();
  }

  close(){
    // Display
    this.element.classList.add('!hidden');

    this.statusRefresher.stop();
  }

  registerEvents() {
    this.closeBtn.addEventListener('click', this.close.bind(this));
  }

  refreshMiniCart() {
    // Use vanilla JS if we can
    jQuery( document.body ).trigger( 'removed_from_cart' );
    // document.body.dispatchEvent(new CustomEvent('removed_from_cart'));
  }

  onPaid(){
    this.refreshMiniCart();

    let span = this.closeBtn.querySelector('span');
    span.innerHTML = this.closeBtn.getAttribute('data-success');
  }

  continue(){
    this.closeBtn.innerHTML = this.closeBtn.getAttribute('data-success');
  }
}

export default Modal;
