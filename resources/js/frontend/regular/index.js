document.addEventListener('DOMContentLoaded', function () {
  jQuery('form.checkout').on(
    'checkout_place_order_success',
    function (type, data) {
      console.log(type, data);
    }
  );
});
