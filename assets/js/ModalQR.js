/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!********************************************!*\
  !*** ./resources/js/frontstore/ModalQR.js ***!
  \********************************************/
document.addEventListener("DOMContentLoaded", function (event) {
  class ModalQR {
    constructor() {
      this.options = {
        closeModalButton: '#twint-close',
        modalSelector: '.modal-popup'
      };
    }
    init() {
      this.closeBtn = document.querySelector(this.options.closeModalButton);
      if (this.closeBtn) {
        this.closeBtn.addEventListener('click', this.onClose.bind(this));
      }
      this.modal = document.querySelector(this.options.modalSelector);
    }
    onClose() {
      this.modal?.classList?.remove('_show');
      console.log('closed');
    }
  }
  new ModalQR().init();
});
/******/ })()
;
//# sourceMappingURL=ModalQR.js.map