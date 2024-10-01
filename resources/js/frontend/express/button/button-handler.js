class ButtonHandler {
  constructor() {
    this.getButtons();
    this.registerEvents();
  }

  getButtons() {
    this.buttons = document.querySelectorAll('.twint-button');
  }

  registerEvents() {
    const self = this; // Retain reference to the class instance
    this.buttons.forEach((button) => {
      // Check if the button has the 'PDP' class
      if (button.classList.contains('PDP') || button.classList.contains('PLP')) {
        // Create a MutationObserver to observe changes in the button's attributes
        const observer = new MutationObserver(function (mutations) {
          mutations.forEach(function (mutation) {
            // Check if the 'disabled' attribute is being mutated
            if (mutation.attributeName === 'class') {
              const disabled = mutation.target.classList.contains('disabled')
              self.syncButtonState(button, disabled); // Call the method to sync button state
            }
          });
        });

        let addToCartButton = button.previousElementSibling;
        if(addToCartButton && (addToCartButton.tagName === 'BUTTON' || addToCartButton.tagName === 'A')) {
          // Observe the button's attribute changes (for 'disabled' attribute)
          observer.observe(addToCartButton, {attributes: true});
        }
      }
    });
  }

  syncButtonState(button, disabled) {
    // Set or remove the 'disabled' attribute based on the value of 'disabled'
    button.disabled = disabled;
  }
}

export default ButtonHandler;
