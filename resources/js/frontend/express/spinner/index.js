import points from './points'

class Spinner {
  static animation = null
  static index = 0
  static container = null

  constructor() {
    this.element = document.getElementById('twint-loading')
    Spinner.container = document.getElementById('twint-animation')
  }

  start() {
    this.element.classList.add('active')

    if (!Spinner.animation) {
      Spinner.animation = setInterval(this.changePoints.bind(this), 20)
    }
  }

  stop() {
    this.element.classList.remove('active')

    if (Spinner.animation) {
      clearInterval(Spinner.animation)
      Spinner.animation = null
    }
  }

  changePoints() {
    Spinner.container.setAttribute('d', String(points[Spinner.index]))

    Spinner.index++
    if (Spinner.index >= points.length) {
      Spinner.index = 0
    }
  }
}

export default Spinner
