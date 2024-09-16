import Connector from './connector';

class AndroidConnector extends Connector{
  constructor() {
    super();
    this.button = document.getElementById('twint-addroid-button');
  }

  init() {
    if (!this.button)
      return;

    this.button.href = this.button.getAttribute('data-href').replace('--TOKEN--', this.token);

    this.button.click();
  }
}

export default AndroidConnector;
