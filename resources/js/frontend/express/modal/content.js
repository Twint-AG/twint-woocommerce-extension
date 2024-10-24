class ModalContent {
  constructor(token, amount, pairing, isExpress = true) {
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
    let element = document.getElementById('twint-amount')
    if (element) {
      element.innerHTML = this.amount
    }
  }

  renderToken() {
    let element = document.getElementById('qr-token')
    if (element) {
      element.value = this.token
    }
  }

  renderQr() {
    let qr = document.getElementById('qrcode')
    qr.innerHTML = ''

    new QRCode(qr, {
      text: this.token,
      width: 300,
      height: 300,
      colorDark: '#000000',
      colorLight: '#ffffff',
      correctLevel: QRCode.CorrectLevel.H,
    })
  }

  adjustGuides() {
    let selectContact = document.getElementById('twint-guide-contact')
    let guides = selectContact.parentElement

    if (this.isExpress) {
      selectContact.classList.remove('hidden')

      if (!selectContact.closest('.twint-mobile'))
        guides.classList.add('md:grid-cols-2')
      else guides.classList.remove('md:grid-cols-2')
    } else {
      selectContact.classList.add('hidden')
      guides.classList.remove('md:grid-cols-2')
    }
  }
}

export default ModalContent
