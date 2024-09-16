import Spinner from './spinner/index';
import Modal from './modal/modal';

const bootstrap = () => {
  let spinner = new Spinner();
  let modal = new Modal();
};

document.addEventListener( 'DOMContentLoaded', () => {
  bootstrap();
});
