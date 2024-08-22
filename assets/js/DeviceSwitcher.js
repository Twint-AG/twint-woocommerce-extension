/******/ (() => { // webpackBootstrap
var __webpack_exports__ = {};
/*!***************************************************!*\
  !*** ./resources/js/frontstore/DeviceSwitcher.js ***!
  \***************************************************/
document.addEventListener("DOMContentLoaded", function (event) {
  class DeviceSwitcher {
    constructor() {
      this.options = {
        // pageSelector: '.twint-qr-container',
        pageSelector: '#qr-modal-content',
        appSelector: '#logo-container',
        qrCodeSelector: ".qr-code",
        appLinkSelector: "#app-chooser",
        appCountDownInterval: 2 //second
      };
    }
    init() {
      var _this$$container$getA, _this$$container$getA2, _this$$container$getA3;
      this.$container = document.querySelector(this.options.pageSelector);
      if (!this.$container) {
        return;
      }
      this.isMobile = (_this$$container$getA = this.$container.getAttribute('data-mobile')) !== null && _this$$container$getA !== void 0 ? _this$$container$getA : false;
      this.isAndroid = (_this$$container$getA2 = this.$container.getAttribute('data-is-android-device')) !== null && _this$$container$getA2 !== void 0 ? _this$$container$getA2 : false;
      this.isIos = (_this$$container$getA3 = this.$container.getAttribute('data-is-ios-device')) !== null && _this$$container$getA3 !== void 0 ? _this$$container$getA3 : false;
      if (this.isIos) {
        this.handleIos();
      }
      if (this.isAndroid) {
        this.handleAndroid();
      }
    }
    handleIos() {
      console.log('handling IOS device...');
      this.$_apps = document.querySelector(this.options.appSelector);
      this.$qrCode = document.querySelectorAll(this.options.qrCodeSelector);
      this.$appLinks = document.querySelector(this.options.appLinkSelector);
      this.$banks = document.querySelectorAll('.bank-logo');
      if (this.$banks) {
        this.$banks.forEach(object => {
          object.addEventListener('touchend', event => {
            this.onClickBank(event, object);
          });
        });
      }
      if (this.$appLinks) {
        this.$appLinks.addEventListener('change', this.onChangeAppList.bind(this));
      }
    }
    handleAndroid() {
      console.log('handling android device...');
      this.$qrCode = document.querySelectorAll(this.options.qrCodeSelector);
      let link = this.$container.getAttribute('data-android-link');
      window.location.replace(link);
      const checkLocation = setInterval(() => {
        this.showMobileQrCode();
        clearInterval(checkLocation);
      }, this.options.appCountDownInterval * 1000);
    }
    onClickBank(event, object) {
      let link = object.getAttribute('data-link');
      this.openAppBank(link);
    }
    onChangeAppList(event) {
      const select = event.target;
      let link = select.options[select.selectedIndex].value;
      this.openAppBank(link);
    }
    openAppBank(link) {
      if (link) {
        try {
          window.location.replace(link);
          const checkLocation = setInterval(() => {
            if (window.location.href !== link) {
              this.showMobileQrCode();
            }
            clearInterval(checkLocation);
          }, this.options.appCountDownInterval * 1000);
        } catch (e) {
          this.showMobileQrCode();
        }
      }
    }
    showMobileQrCode() {
      this.$qrCode.forEach(object => {
        object?.classList?.remove('d-none');
      });
    }
  }
  new DeviceSwitcher().init();
});
/******/ })()
;
//# sourceMappingURL=DeviceSwitcher.js.map