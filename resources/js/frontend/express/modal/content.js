import QRCode from 'qrcode'
import DOMPurify from 'dompurify'

class ModalContent {
  constructor(shadow, token, amount, pairing, isExpress = true) {
    this.shadow = shadow

    this.token = token
    this.amount = amount
    this.pairing = pairing
    this.isExpress = isExpress
  }

  render() {
    this.renderAmount()
    this.renderToken()
    this.renderQr()
    this.adjustGuides()
  }

  renderAmount() {
    let element = this.shadow.querySelector('#twint-amount')
    if (element) {
      element.innerHTML = DOMPurify.sanitize(this.amount)
    }
  }

  renderToken() {
    let element = this.shadow.querySelector('#qr-token')
    if (element) {
      element.value = this.token
    }
  }

  renderQr() {
    let qr = this.shadow.querySelector('#qrcode')
    qr.innerHTML = ''

    QRCode.toCanvas(qr, this.token, {
      width: 300,
      height: 300,
      colorDark: '#000000',
      colorLight: '#ffffff',
    })
  }

  adjustGuides() {
    let selectContact = this.shadow.querySelector('#twint-guide-contact')
    let guides = selectContact.parentElement

    if (this.isExpress) {
      selectContact.classList.remove('tw-hidden')

      if (!selectContact.closest('.twint-mobile'))
        guides.classList.add('md:tw-grid-cols-2')
      else guides.classList.remove('md:tw-grid-cols-2')
    } else {
      selectContact.classList.add('tw-hidden')
      guides.classList.remove('md:tw-grid-cols-2')
    }
  }
}

export default ModalContent
