import Spinner from "../spinner";
import triggerFetch from '@wordpress/api-fetch';

class Action {
  static spinner;

  constructor() {
    if (!Action.spinner)
      Action.spinner = new Spinner();
  }

  handle(context, onSuccessCallback, onFailureCallback) {
    Action.spinner.start();

    triggerFetch({
      path: '/twint/v1/express/checkout',
      method: 'POST',
      data: context.getParams(),
      cache: 'no-store',
      parse: false,
    })
      .then(response => {
        triggerFetch.setNonce(response.headers);

        Action.spinner.stop();
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }

        return response.json();
      })
      .then(data => {
        onSuccessCallback(data);
      })
      .catch((error) => {
        Action.spinner.stop();
        console.error('Error:', error);
        onFailureCallback(error)
      });
  }
}

export default Action;
