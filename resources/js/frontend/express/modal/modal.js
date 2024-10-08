import StatusRefresher from "./handler/status-refresher";
import TokenCopier from "./handler/token-copier";
import AndroidConnector from "./connector/android-connector";
import IosConnector from "./connector/ios-connector";

class Modal {
  static EVENT_CLOSED = 'CLOSED';

  constructor() {
    this.element = document.getElementById('twint-modal');
    this.closeBtn = this.element.querySelector('#twint-close');

    // Handlers
    this.statusRefresher = new StatusRefresher();
    this.tokenCopier = new TokenCopier();

    this.connectors = [];
    this.connectors.push(new AndroidConnector());
    this.connectors.push(new IosConnector());

    this.registerEvents();

    this.callbacks = {};
  }

  getData() {
    return {
      label: this.element.getAttribute('data-exist-label'),
      message: this.element.getAttribute('data-exist-message')
    };
  }

  setContent(content) {
    this.content = content;
  }

  addCallback(event, callback) {
    if (typeof callback === 'function') {
      this.callbacks[event] = callback;
    }
  }

  show() {
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

  close() {
    // Display
    this.element.classList.add('!hidden');

    // Default handlers
    this.statusRefresher.stop();

    let callback = this.callbacks[Modal.EVENT_CLOSED];
    if (callback) {
      callback();
    }
  }

  registerEvents() {
    this.closeBtn.addEventListener('click', this.close.bind(this));
  }

  refreshMiniCart() {
    // Refresh mini-cart if not in cart page to prevent reload the whole page
    if (!document.body.classList.contains('woocommerce-cart')) {
      jQuery(document.body).trigger('wc_fragment_refresh');
      jQuery(document.body).trigger('added_to_cart');
      jQuery(document.body).trigger('removed_from_cart');
      jQuery(document.body).trigger('wc-blocks_removed_from_cart');
      jQuery(document.body).trigger('wc-blocks_added_to_cart');
    }
  }

  onPaid(response) {
    this.refreshMiniCart();

    let span = this.closeBtn.querySelector('span');
    span.innerHTML = this.closeBtn.getAttribute('data-success');

    location.href = response.extra.redirect;
  }

  continue() {
    this.closeBtn.innerHTML = this.closeBtn.getAttribute('data-success');
  }
}

export default Modal;
