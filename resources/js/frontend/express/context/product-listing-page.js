import Context from "./context";

class ProductListingPageContext extends Context {
  constructor(button) {
    super(button);
  }

  getParams() {
    const box = this.button.previousElementSibling;

    const dataBlockButton = box.getAttribute('data-block-name');
    if (!dataBlockButton) {
      /**
       * `box` does not exist, then we need to handle to support NON-Blocks
       * @type {HTMLCollection}
       */
      const children = this.button.parentElement.children;
      let btnAddToCart = null;
      for (let i = 0; i < children.length; i++) {
        for (let j = 0; j < children[i].classList.length; j++) {
          if (children[i].classList[j] === 'ajax_add_to_cart') {
            btnAddToCart = children[i];
            break;
          }
        }

        if (btnAddToCart !== null) {
          break;
        }
      }

      if (btnAddToCart !== null) {
        const productId = btnAddToCart.attributes['data-product_id']?.value;
        const quantity = btnAddToCart.attributes['data-quantity']?.value;

        return {
          quantity: quantity,
          id: productId,
        };
      } else {
        throw new Error('Cannot find parent product box');
      }
    }

    const json = box.getAttribute('data-wc-context');

    return this.mapping(JSON.parse(json));
  }

  mapping(data) {
    return {
      quantity: data.quantityToAdd,
      id: data.productId
    }
  }
}

export default ProductListingPageContext;
