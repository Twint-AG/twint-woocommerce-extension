import Clipboard from './librabries/clipboard';

document.addEventListener("DOMContentLoaded", function(event) {
    class CopyToken {
        constructor() {
            this.options = {
                selector: '#btn-copy-token',
                target: '#qr-token',
            };

        }

        init() {
            this.input = document.querySelector(this.options.target);

            this.button = document.querySelector(this.options.selector);
            if (this.button) {
                this.button.addEventListener('click', this.onClick.bind(this));

                this.clipboard = new Clipboard(this.options.selector);
                this.clipboard.on('success', this.onCopied.bind(this));
                this.clipboard.on('error', this.onError.bind(this));
            }

        }

        btnOriginalText() {
            return this.button.textContent;
        }

        onClick(event) {
            event.preventDefault();
            this.input.disabled = false;
        }

        onCopied(e) {
            e.clearSelection();
            const originalText = this.btnOriginalText();

            this.button.innerHTML = 'Copied!';
            this.button?.classList?.add('copied');
            this.input.disabled = true;
            this.button.setAttribute('disabled', '');

            setTimeout(() => {
                this.button.innerHTML = originalText;
                this.button?.classList?.remove('copied');

                this.button.innerHTML = originalText;
                this.input.disabled = false;
                this.button.removeAttribute('disabled');
            }, 2000);
        }

        onError(e) {
            console.error('Action:', e.action);
            console.error('Trigger:', e.trigger);
        }
    }

    new CopyToken().init();
});
