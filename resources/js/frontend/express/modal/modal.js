import StatusRefresher from "./handler/status-refresher";

class Modal {
  constructor(){
    this.element = document.getElementById('twint-modal');
    this.closeBtn = this.element.querySelector('#twint-close');

    // Handlers
    this.statusRefresher = new StatusRefresher();

    this.registerEvents();
  }

  setContent(content){
    this.content = content;
  }

  show(){
    // Display
    let span = this.closeBtn.querySelector('span');
    span.innerHTML = this.closeBtn.getAttribute('data-default');

    // prepare modal content
    this.content.render();

    //Show modal
    this.element.classList.remove('!hidden');

    //Events
    this.statusRefresher.setPairing(this.content.pairing);
    this.statusRefresher.addCallBack(StatusRefresher.EVENT_CANCELLED, this.close.bind(this));
    this.statusRefresher.addCallBack(StatusRefresher.EVENT_PAID, this.onPaid.bind(this));
    this.statusRefresher.start();
  }

  close(){
    // Display
    this.element.classList.add('!hidden');

    this.statusRefresher.stop();
  }

  registerEvents() {
    this.closeBtn.addEventListener('click', this.close.bind(this));
  }

  onPaid(){
    let span = this.closeBtn.querySelector('span');
    span.innerHTML = this.closeBtn.getAttribute('data-success');
  }

  continue(){
    this.closeBtn.innerHTML = this.closeBtn.getAttribute('data-success');
  }
}

export default Modal;
