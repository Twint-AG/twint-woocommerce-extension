import ModalContent from '../express/modal/content'
import Modal from '../express/modal/modal'

document.addEventListener('DOMContentLoaded', function () {
  jQuery('form.checkout').on(
    'checkout_place_order_success',
    function (type, data) {
      console.log(type, data)
      if (data.result === 'success') {
        let modal = new Modal(Modal.TYPE_REGULAR_CHECKOUT)

        const { pairingToken, amount, pairingId } = data
        modal.setContent(
          new ModalContent(pairingToken, amount, pairingId, false),
        )

        modal.show()
      }
    },
  )
})
