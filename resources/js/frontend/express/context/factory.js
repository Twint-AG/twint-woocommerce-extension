import ProductDetailsPageContext from './product-details-page';
import Context from "./context";
import ProductListingPageContext from "./product-listing-page";
import CartContext from "./cart";

class ContextFactory {
  static getContext(button) {
    if (ContextFactory.inPDP(button))
      return new ProductDetailsPageContext(button);

    if (ContextFactory.inPLP(button))
      return new ProductListingPageContext(button);

    if (ContextFactory.inCart(button))
      return new CartContext(button);

    return new Context(button);
  }

  static inPDP(button) {
    return button.classList.contains('PDP');
  }

  static inPLP(button) {
    return button.classList.contains('PLP');
  }

  static inCart(button) {
    return button.classList.contains('mini-cart') || button.classList.contains('PLP');
  }
}

export default ContextFactory;
