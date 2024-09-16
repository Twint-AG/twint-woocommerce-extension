class TokenCopier {
  constructor() {
    let inputId = 'qr-token';
    let buttonId = 'twint-copy-btn';

    this.input = document.getElementById(inputId);
    this.button = document.getElementById(buttonId);

    this.button.addEventListener('click', this.onClick.bind(this));


    this.clipboard = new Clipboard('#' + buttonId);
    this.clipboard.on('success', this.onCopied.bind(this));
    this.clipboard.on('error', this.onError.bind(this));
  }

  onClick(event) {
    event.preventDefault();
    this.input.disabled = false;
  }

  onCopied(e) {
    e.clearSelection();
    this.button.innerHTML = this.button.getAttribute('data-copied');
    this.button.classList.add('copied');
    this.button.classList.add('border-green-500');
    this.button.classList.add('text-green-500');
    this.input.disabled = true;

    setTimeout(this.reset.bind(this), 10000);
  }

  onError(e) {
    console.error('Action:', e.action);
    console.error('Trigger:', e.trigger);
  }

  reset() {
    this.button.innerHTML = this.button.getAttribute('data-default');
    this.button.classList.remove('copied');
    this.button.classList.remove('border-green-500');
    this.button.classList.remove('text-green-500');
  }
}

export default TokenCopier;
