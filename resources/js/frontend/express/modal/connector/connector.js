class Connector {
  constructor(shadow) {
    this.shadow = shadow
  }

  init() {
    // in child classes
  }

  setToken(token) {
    this.token = token
  }
}

export default Connector
