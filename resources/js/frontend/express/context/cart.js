import Context from './context'

class CartContext extends Context {
  constructor(button) {
    super(button)
  }

  getParams() {
    return {
      full: true,
    }
  }
}

export default CartContext
