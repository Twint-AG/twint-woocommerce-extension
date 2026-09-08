import apiFetch from '@wordpress/api-fetch'
import IntervalHandler from './interval-handler'
import Modal from '../modal'

class StatusRefresher {
  static EVENT_CANCELLED = 'cancelled'
  static EVENT_PAID = 'paid'
  static EVENT_FAILED = 'failed'

  constructor(modal) {
    this.element = document.getElementById('twint-modal')
    this.intervalHanlder = new IntervalHandler({
      0: 5000,
      5: 2000,
      600: 10000, //10 mins
      3600: 0, // 1 hour
    })

    this.modal = modal

    this.processing = false
    this.finished = false
    this.stopped = false

    this.pairing = null

    this.callbacks = {}
  }

  setPairing(value) {
    this.pairing = value
  }

  addCallBack(name, callback) {
    this.callbacks[name] = callback
  }

  start() {
    this.stopped = false
    this.finished = false
    this.processing = false

    this.intervalHanlder.begin()
    if (this.modal.isExpress()) {
      this.modal.addCallback(
        Modal.EVENT_MODAL_CLOSED,
        this.onModalClosed.bind(this),
      )
    } else {
      this.modal.addCallback(
        Modal.EVENT_MODAL_CLOSED,
        this.onRegularCheckoutCloseModal.bind(this),
      )
    }

    this.onProcessing()
  }

  async onModalClosed() {
    this.stop()

    if (!this.finished) {
      const self = this

      return await this.cancelPayment((data) => {
        if (data.success !== true) {
          setTimeout(() => self.check(true), 500)
        }
      })
    }
  }

  cancelPayment(callback) {
    this.processing = true

    return apiFetch({
      path: '/twint/v1/payment/cancel',
      method: 'POST',
      data: {
        pairingId: this.pairing,
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

  stop() {
    this.stopped = true
  }

  onProcessing() {
    this.finished = false
    if (this.processing || this.stopped) return

    let interval = this.intervalHanlder.interval()
    if (interval > 0) {
      setTimeout(this.check.bind(this), interval)
    }
  }

  onPaid(response) {
    this.finished = true
    let callback = this.callbacks[StatusRefresher.EVENT_PAID]
    if (callback) {
      callback(response)
    }
  }

  onCancelled() {
    this.finished = true

    let callback = this.callbacks[StatusRefresher.EVENT_CANCELLED]
    if (callback && typeof callback === 'function') {
      callback()
    }
  }

  onFailed() {
    this.finished = true

    let callback = this.callbacks[StatusRefresher.EVENT_FAILED]
    if (callback && typeof callback === 'function') {
      callback()
    }
  }

  onFinish(response) {
    this.finished = true

    if (response.finish && response.status === 'PAID') {
      return this.onPaid(response)
    }

    if (response.finish && response.status === 'FAILED') {
      return this.onFailed()
    }

    return this.onCancelled(response)
  }

  isIOS() {
    const ua = window.navigator.userAgent
    const platform = window.navigator.platform
    return (
      /iPad|iPhone|iPod/.test(ua) ||
      (platform === 'MacIntel' && navigator.maxTouchPoints > 1)
    )
  }

  check(oneTime = false) {
    if (!oneTime && (this.stopped || this.processing)) return

    const self = this
    this.processing = true

    let controller
    let timeoutId

    let payload = {
      path: '/twint/v1/payment/status',
      method: 'POST',
      data: {
        pairingId: this.pairing,
      },
      cache: 'no-store',
      parse: false,
    }

    if (this.isIOS() && typeof AbortController !== 'undefined') {
      controller = new AbortController()
      timeoutId = setTimeout(() => controller.abort(), 3000) // 3 seconds timeout

      payload.signal = controller.signal
    }

    apiFetch(payload)
      .then((response) => {
        if (timeoutId) clearTimeout(timeoutId)
        self.processing = false

        if (!response.ok) {
          throw new Error('Network response was not ok')
        }

        return response.json()
      })
      .then((data) => {
        if (data.finish === true) return self.onFinish(data)

        !oneTime && self.onProcessing()
      })
      .catch((error) => {
        if (timeoutId) clearTimeout(timeoutId)
        self.processing = false

        if (
          error.name === 'AbortError' ||
          (error.code && error.code === 'fetch_error')
        ) {
          return self.check()
        }

        // Any other transient error (e.g. HTTP 500): keep polling instead of giving up
        console.error('TWINT status poll error:', error)
        if (!oneTime && !self.stopped && !self.finished) {
          self.onProcessing()
        }
      })
  }

  async onRegularCheckoutCloseModal() {
    if (!this.finished) {
      const self = this
      return await this.cancelPayment(function (data) {
        if (data.success === true) {
          location.reload()
        } else {
          setTimeout(() => self.check(true), 500)
        }
      })
    }
  }
}

export default StatusRefresher
