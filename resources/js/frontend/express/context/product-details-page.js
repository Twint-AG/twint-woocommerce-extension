import Context from "./context";

class ProductDetailsPageContext extends Context {
  constructor(button) {
    super(button);
  }

  getParams() {
    const form = this.button.closest('form');

    if (!form) {
      throw new Error('Cannot find product form');
    }

    let data = Object.fromEntries(new FormData(form));
    if (!data.product_id) {
      let addToCartBtn = form.querySelector('[name="add-to-cart"]');
      if (addToCartBtn) {
        data.product_id = addToCartBtn.value;
      }
    }

    return data;
  }
}


export default ProductDetailsPageContext;
