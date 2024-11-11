import StatusRefresher from './handler/status-refresher'
import TokenCopier from './handler/token-copier'
import AndroidConnector from './connector/android-connector'
import IosConnector from './connector/ios-connector'
import DOMPurify from "dompurify";

class Modal {
  static EVENT_MODAL_CLOSED = 'CLOSED'
  static TYPE_EXPRESS_CHECKOUT = 'EC'
  static TYPE_REGULAR_CHECKOUT = 'RC'

  constructor(checkoutType) {
    this.element = document.getElementById('twint-modal')
    this.closeBtn = this.element.querySelector('#twint-close')

    // Handlers
    this.statusRefresher = new StatusRefresher(this)
    this.tokenCopier = new TokenCopier()

    this.connectors = []
    this.connectors.push(new AndroidConnector())
    this.connectors.push(new IosConnector())

    this.registerEvents()
    this.checkout = checkoutType
    this.callbacks = {}
  }

  isExpress() {
    return this.checkout === Modal.TYPE_EXPRESS_CHECKOUT
  }

  getData() {
    return {
      label: this.element.getAttribute('data-exist-label'),
      message: this.element.getAttribute('data-exist-message'),
    }
  }

  setContent(content) {
    this.content = content
  }

  addCallback(event, callback) {
    if (typeof callback === 'function') {
      this.callbacks[event] = callback
    }
  }

  show() {
    // Display
    let span = this.closeBtn.querySelector('span')
    span.innerHTML = DOMPurify.sanitize(this.closeBtn.getAttribute('data-default'))

    this.tokenCopier.reset()

    // prepare modal content
    this.content.render()

    let pay = this.element.querySelector('#qr-modal-content')
    let errorContent = this.element.querySelector('#payment-error')

    errorContent.style.display = 'none'
    pay.style.display = 'block'

    //Show modal
    this.element.classList.remove('!hidden')

    //Connector
    this.connectors.forEach((connector) => {
      connector.setToken(this.content.token)
      connector.init()
    })

    //Events
    this.statusRefresher.setPairing(this.content.pairing)
    this.statusRefresher.addCallBack(
      StatusRefresher.EVENT_CANCELLED,
      this.close.bind(this),
    )
    this.statusRefresher.addCallBack(
      StatusRefresher.EVENT_PAID,
      this.onPaid.bind(this),
    )
    this.statusRefresher.addCallBack(
      StatusRefresher.EVENT_FAILED,
      this.onFailed.bind(this),
    )

    this.statusRefresher.start()
  }

  close() {
    // Display
    this.element.classList.add('!hidden')

    let callback = this.callbacks[Modal.EVENT_MODAL_CLOSED]
    if (callback) {
      callback()
    }
  }

  registerEvents() {
    this.closeBtn.addEventListener('click', this.close.bind(this))
  }

  refreshMiniCart() {
    // Refresh mini-cart if not in cart page to prevent reload the whole page
    if (!document.body.classList.contains('woocommerce-cart')) {
      jQuery(document.body).trigger('wc_fragment_refresh')
      jQuery(document.body).trigger('added_to_cart')
      jQuery(document.body).trigger('removed_from_cart')
      jQuery(document.body).trigger('wc-blocks_removed_from_cart')
      jQuery(document.body).trigger('wc-blocks_added_to_cart')
    }
  }

  onPaid(response) {
    this.refreshMiniCart()

    let span = this.closeBtn.querySelector('span')
    span.innerHTML = DOMPurify.sanitize(this.closeBtn.getAttribute('data-success'))

    location.href = response.extra.redirect
  }

  onFailed() {
    // Display
    let span = this.closeBtn.querySelector('span')
    span.innerHTML = DOMPurify.sanitize(this.closeBtn.getAttribute('data-success'))

    let pay = this.element.querySelector('#qr-modal-content')
    let errorContent = this.element.querySelector('#payment-error')

    errorContent.style.display = 'block'
    pay.style.display = 'none'
  }

  continue() {
    this.closeBtn.innerHTML = DOMPurify.sanitize(this.closeBtn.getAttribute('data-success'))
  }
}

export default Modal
