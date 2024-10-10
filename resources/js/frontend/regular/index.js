import ModalContent from "../express/modal/content";
import Modal from "../express/modal/modal";

document.addEventListener('DOMContentLoaded', function () {
  jQuery('form.checkout').on(
    'checkout_place_order_success',
    function (type, data) {
      console.log(type, data);
      if (data.result === 'success') {
        let modal = new Modal();

        modal.addCallback(Modal.EVENT_MODAL_CLOSED, ()=> {
          location.reload();
        });

        const {
          pairingToken,
          amount,
          pairingId,
          currency,
        } = data;
        modal.setContent(new ModalContent(
          pairingToken,
          amount + currency,
          pairingId,
          false
        ));

        modal.show();
      }
    }
  );
});
