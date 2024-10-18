import Spinner from '../spinner'
import apiFetch from '@wordpress/api-fetch'

class Action {
  static spinner

  constructor() {
    if (!Action.spinner) Action.spinner = new Spinner()
  }

  handle(context, onSuccessCallback, onFailureCallback) {
    Action.spinner.start()

    apiFetch({
      path: '/twint/v1/express/checkout',
      method: 'POST',
      data: context.getParams(),
      cache: 'no-store',
      parse: false,
    })
      .then((response) => {
        Action.spinner.stop()
        console.log(response)
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
        Action.spinner.stop()
        console.error('Error:', error)
        onFailureCallback(error)
      })
  }
}

export default Action
