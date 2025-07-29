import Spinner from '../spinner'
import apiFetch from '@wordpress/api-fetch'

class Action {
  static spinner
  static processing = false

  constructor() {
    if (!Action.spinner) Action.spinner = new Spinner()
  }

  handle(context, onSuccessCallback, onFailureCallback) {
    if (Action.processing) return

    Action.processing = true
    Action.spinner.start()

    apiFetch({
      path: '/twint/v1/express/checkout',
      method: 'POST',
      data: context.getParams(),
      cache: 'no-store',
      parse: false,
    })
      .then((response) => {
        Action.processing = false
        Action.spinner.stop()
        if (!response.ok) {
          throw new Error('Network response was not ok')
        }

        return response.json()
      })
      .then((data) => {
        if ('success' in data && data.success === false)
          return onFailureCallback(data)

        return onSuccessCallback(data)
      })
      .catch((error) => {
        Action.processing = false
        Action.spinner.stop()
        console.error('Error:', error)
        onFailureCallback(error)
      })
  }
}

export default Action
