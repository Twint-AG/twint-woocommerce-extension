import apiFetch from '@wordpress/api-fetch';
import IntervalHandler from "./interval-handler";
import Modal from "../modal";

class StatusRefresher {
  static EVENT_CANCELLED = 'cancelled';
  static EVENT_PAID = 'paid';

  constructor(modal) {
    this.intervalHanlder = new IntervalHandler({
      0: 5000,
      5: 2000,
      600: 10000, //10 mins
      3600: 0 // 1 hour
    });

    this.modal = modal;

    this.processing = false;
    this.finished = false;
    this.stopped = false;

    this.pairing = null;

    this.callbacks = {};
  }

  setPairing(value) {
    this.pairing = value;
  }

  addCallBack(name, callback) {
    this.callbacks[name] = callback;
  }

  start() {
    this.stopped = false;
    this.finished = false;
    this.intervalHanlder.begin();
    if(this.modal.isExpress()) {
      this.modal.addCallback(Modal.EVENT_MODAL_CLOSED, this.onModalClosed.bind(this));
    }else {
      this.modal.addCallback(Modal.EVENT_MODAL_CLOSED, this.onRegularCheckoutCloseModal.bind(this));
    }
    this.onProcessing();
  }

  onModalClosed(){
    this.stop();

    if(!this.finished){
      const  self = this;

      this.cancelPayment(data => {
        if (data.success !== true) {
          return self.check(true);
        }
      });
    }
  }

  cancelPayment(callback){
    this.processing = true;

    apiFetch({
      path: '/twint/v1/payment/cancel',
      method: 'POST',
      data: {
        pairingId: this.pairing
      },
      cache: 'no-store',
      parse: false,
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        return response.json();
      })
      .then(data => {
        return callback(data);
      })
      .catch((error) => {
        console.error('Error:', error);
      });
  }

  stop() {
    this.stopped = true;
  }

  onProcessing() {
    this.finished = false;
    if (this.processing || this.stopped)
      return;

    let interval = this.intervalHanlder.interval();
    if (interval > 0) {
      setTimeout(this.check.bind(this), interval);
    }
  }

  onPaid(response) {
    this.finished = true;
    let callback = this.callbacks[StatusRefresher.EVENT_PAID];
    if (callback) {
      callback(response);
    }
  }

  onCancelled() {
    this.finished = true;

    let callback = this.callbacks[StatusRefresher.EVENT_CANCELLED];
    if (callback && typeof callback === 'function') {
      callback();
    }
  }

  onFinish(response) {
    this.finished = true;

    if (response.finish && response.status === 'PAID') {
      return this.onPaid(response);
    }

    if (response.finish && response.status === 'FAILED') {
      return this.onCancelled();
    }
  }

  check(oneTime = false) {
    if (!oneTime && (this.stopped || this.processing))
      return;

    const self = this;
    this.processing = true;

    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 seconds timeout

    apiFetch({
      path: '/twint/v1/payment/status',
      method: 'POST',
      data: {
        pairingId: this.pairing
      },
      cache: 'no-store',
      parse: false,
      signal: controller.signal
    })
      .then(response => {
        clearTimeout(timeoutId);
        self.processing = false;

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        return response.json();
      })
      .then(data => {
        if (data.finish === true)
          return self.onFinish(data);

        !oneTime && self.onProcessing();
      })
      .catch((error) => {
        clearTimeout(timeoutId);
        self.processing = false;

        if(error.name === 'AbortError'){
          self.check();
        }
      });
  }

  onRegularCheckoutCloseModal(){
    if(!this.finished){
      this.cancelPayment(function(data){
        if(data.success === true){
          location.reload();
        }else {
          this.check(true);
        }
      })
    }
  }
}

export default StatusRefresher;
