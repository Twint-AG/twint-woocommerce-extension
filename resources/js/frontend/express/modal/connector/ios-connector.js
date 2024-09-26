import Connector from './connector';

class IosConnector extends Connector{
  constructor() {
    super();
    this.container = document.getElementById('twint-ios-container');

    this.registeredEvents = false;
  }

  init() {
    if (!this.container || this.registeredEvents)
      return;

    this.banks = this.container.querySelectorAll('img');
    if (this.banks) {
      this.banks.forEach((bank) => {
        bank.addEventListener('touchend', (event) => {
          this.onClickedBank(event, bank);
        });
      });
    }

    this.appLinksElements = this.container.querySelector('select');
    if (this.appLinksElements) {
      this.appLinksElements.addEventListener('change', this.onChangeAppList.bind(this))
    }

    this.registeredEvents = true;
  }

  onChangeAppList(event) {
    const select = event.target;
    let link = select.options[select.selectedIndex].value;

    this.openAppBank(link);
  }

  onClickedBank(event, bank) {
    const link = bank.getAttribute('data-link');

    this.openAppBank(link);
  }

  openAppBank(link) {
    if (link) {
      link = link.replace('--TOKEN--', this.token);

      try {
        window.location.replace(link);

        const checkLocation = setInterval(() => {
          clearInterval(checkLocation);
        }, 2000);
      } catch (e) {

      }
    }
  }
}

export default IosConnector;
