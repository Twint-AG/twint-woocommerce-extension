class ButtonHandler {
  constructor() {
    this.getButtons();
    this.registerEvents();
  }

  getButtons(){
    this.buttons = document.querySelectorAll('.twint-button');
  }

  registerEvents(){

  }
}

export default ButtonHandler;
