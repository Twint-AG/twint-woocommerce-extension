import IntervalHandler from "./interval-handler";
import axios from "axios";

class StatusRefresher {
  static EVENT_CANCELLED = 'cancelled';
  static EVENT_PAID = 'paid';

  constructor() {
    this.intervalHanlder = new IntervalHandler({
      0: 5000,
      5: 2000,
      600: 10000, //10 mins
      3600: 0 // 1 hour
    });

    this.processing = false;

    this.pairing = null;

    this.callbacks = {};
  }

  setPairing(value){
    this.pairing = value;
  }

  addCallBack(name, callback) {
    this.callbacks[name] = callback;
  }

  start() {
    this.stopped = false;
    this.intervalHanlder.begin();

    this.onProcessing();
  }

  stop() {
    this.stopped = true;
  }

  onProcessing() {
    if (this.processing || this.stopped)
      return;

    let interval = this.intervalHanlder.interval();
    if (interval > 0) {
      setTimeout(this.check.bind(this), interval);
    }
  }

  onPaid(response) {
    let callback = this.callbacks[StatusRefresher.EVENT_PAID];
    if (callback) {
      callback(response);
    }
  }

  onCancelled() {
    let callback = this.callbacks[StatusRefresher.EVENT_CANCELLED];
    if (callback && typeof callback === 'function') {
      callback();
    }
  }

  onFinish(response) {
    // Refresh cart

    if (response.finish && response.status > 0) {
      return this.onPaid(response);
    }

    if (response.finish && response.status < 0) {
      return this.onCancelled();
    }
  }

  check() {
    if (this.stopped || this.processing)
      return;

    const self = this;
    this.processing = true;

    let url = woocommerce_params.ajax_url;

    let formData = new FormData();
    formData.append('action', 'twint_check_pairing_status');
    formData.append('pairingId', this.pairing);
    // formData.append('nonce', nonce);

    return axios.post(url, formData).then(response => {
        self.processing = false;

        if (response.finish === true)
          return self.onFinish(response);

        return self.onProcessing();
      }
    );
  }
}

export default StatusRefresher;
