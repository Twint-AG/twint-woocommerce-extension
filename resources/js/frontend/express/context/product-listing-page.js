import Context from "./context";

class ProductListingPageContext extends Context {
  constructor(button) {
    super(button);
  }

  getParams() {
    const box = this.button.closest('[data-block-name="woocommerce/product-button"]');

    if (!box) {
      throw new Error('Cannot find parent product box');
    }

    const json = box.getAttribute('data-wc-context');

    return JSON.parse(json);
  }
}

export default ProductListingPageContext;
