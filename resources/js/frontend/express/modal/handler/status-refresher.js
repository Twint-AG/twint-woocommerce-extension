import triggerFetch from '@wordpress/api-fetch';
import IntervalHandler from "./interval-handler";

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
    if (response.finish && response.status === 'PAID') {
      return this.onPaid(response);
    }

    if (response.finish && response.status === 'FAILED') {
      return this.onCancelled();
    }
  }

  check() {
    if (this.stopped || this.processing)
      return;

    const self = this;
    this.processing = true;

    triggerFetch({
      path: '/twint/v1/payment/status',
      method: 'POST',
      data: {
        pairingId: this.pairing
      },
      cache: 'no-store',
      parse: false,
    })
      .then(response => {
        self.processing = false;
        triggerFetch.setNonce(response.headers);

        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        return response.json();
      })
      .then(data => {
        if (data.finish === true)
          return self.onFinish(data);

        self.onProcessing();
      })
      .catch((error) => {
        self.processing = false;
        console.error('Error:', error);
      });
  }
}

export default StatusRefresher;
