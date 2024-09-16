class Modal {
  constructor(){
    let qr = document.getElementById("qrcode");
    qr.innerHTML = '';

    new QRCode(qr, {
      text: '123123',
      width: 300,
      height: 300,
      colorDark: "#000000",
      colorLight: "#ffffff",
      correctLevel: QRCode.CorrectLevel.H
    });
  }
}

export default Modal;
