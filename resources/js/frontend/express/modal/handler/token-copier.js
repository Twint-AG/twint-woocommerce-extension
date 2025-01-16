import DOMPurify from 'dompurify'

class TokenCopier {
  constructor(shadow) {
    this.shadow = shadow
    let inputId = 'qr-token'
    let buttonId = 'twint-copy-btn'

    this.input = this.shadow.querySelector(`#${inputId}`)
    this.button = this.shadow.querySelector(`#${buttonId}`)

    this.button.addEventListener('click', this.onClick.bind(this))
  }

  onClick(event) {
    event.preventDefault()
    this.copyToClipboard(this.input.value)
  }

  copyToClipboard(text) {
    try {
      navigator.clipboard
        .writeText(text)
        .then(() => {
          this.onCopied()
        })
        .catch((err) => {
          console.error('Failed to copy text: ', err)
        })
      // eslint-disable-next-line @typescript-eslint/no-unused-vars
    } catch (_e) {
      /* empty */
    }
  }

  onCopied() {
    this.button.innerHTML = DOMPurify.sanitize(
      this.button.getAttribute('data-copied'),
    )
    this.button.classList.add('copied')
    this.button.classList.add('tw-border-green-500')
    this.button.classList.add('tw-text-green-500')
    this.input.disabled = true

    setTimeout(this.reset.bind(this), 10000)
  }

  reset() {
    this.button.innerHTML = DOMPurify.sanitize(
      this.button.getAttribute('data-default'),
    )
    this.button.classList.remove('copied')
    this.button.classList.remove('tw-border-green-500')
    this.button.classList.remove('tw-text-green-500')
  }
}

export default TokenCopier
