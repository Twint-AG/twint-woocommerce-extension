import Context from './context'

class ProductDetailsPageContext extends Context {
  constructor(button) {
    super(button)
  }

  getParams() {
    const form = this.button.closest('form')

    if (!form) {
      throw new Error('Cannot find product form')
    }

    let data = Object.fromEntries(new FormData(form))
    if (!data.product_id) {
      let addToCartBtn = form.querySelector('[name="add-to-cart"]')
      if (addToCartBtn) {
        data.product_id = addToCartBtn.value
      }
    }

    return this.mapping(data)
  }

  mapping(data) {
    // Initialize the object to hold the variant attributes
    let variation = []

    // Loop through the object and separate attributes that start with "attribute_"
    for (const key in data) {
      if (key.startsWith('attribute_')) {
        variation.push({
          attribute: key.replace('attribute_', ''),
          value: data[key],
        })
      }
    }

    return {
      id: data.variation_id ?? data.product_id,
      quantity: data.quantity,
      variation: variation,
    }
  }
}

export default ProductDetailsPageContext
