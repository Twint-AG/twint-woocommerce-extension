import ModalContent from '../express/modal/content'
import Modal from '../express/modal/modal'
import apiFetch from '@wordpress/api-fetch'

class TwintOrderPay {
  render(data) {
    let modal = new Modal(window.twintShadowRoot, Modal.TYPE_REGULAR_CHECKOUT)

    const { token, amount, id } = data
    modal.setContent(
      new ModalContent(window.twintShadowRoot, token, amount, id, false),
    )

    modal.show()
  }

  getInformation(callback) {
    const urlParams = new URLSearchParams(window.location.search)
    const pairing = urlParams.get('pairing')

    if (pairing && pairing.trim() !== '') {
      apiFetch({
        path: '/twint/v1/payment/information',
        method: 'POST',
        data: {
          pairingId: pairing,
        },
        cache: 'no-store',
        parse: false,
      })
        .then((response) => {
          if (!response.ok) {
            throw new Error('Network response was not ok')
          }

          return response.json()
        })
        .then((data) => {
          return callback(data)
        })
        .catch((error) => {
          console.error('Error:', error)
        })
    }
  }

  handle() {
    const self = this
    this.getInformation(function (response) {
      if (!response.finished) {
        self.render(response)
      }
    })
  }
}

document.addEventListener('DOMContentLoaded', () => {
  let handler = new TwintOrderPay()
  handler.handle()
})

export default TwintOrderPay
