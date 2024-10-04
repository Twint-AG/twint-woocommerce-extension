/*! For license information please see checkout.js.LICENSE.txt */
(()=>{"use strict";var t={n:e=>{var n=e&&e.__esModule?()=>e.default:()=>e;return t.d(n,{a:n}),n},d:(e,n)=>{for(var r in n)t.o(n,r)&&!t.o(e,r)&&Object.defineProperty(e,r,{enumerable:!0,get:n[r]})},o:(t,e)=>Object.prototype.hasOwnProperty.call(t,e)};const e=window.wp.i18n,n=window.wc.wcBlocksRegistry,r=window.wc.wcSettings,o=window.wp.element,i=window.wp.apiFetch;var a=t.n(i);function c(t){return c="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},c(t)}function u(t,e){return function(t){if(Array.isArray(t))return t}(t)||function(t,e){var n=null==t?null:"undefined"!=typeof Symbol&&t[Symbol.iterator]||t["@@iterator"];if(null!=n){var r,o,i,a,c=[],u=!0,s=!1;try{if(i=(n=n.call(t)).next,0===e){if(Object(n)!==n)return;u=!1}else for(;!(u=(r=i.call(n)).done)&&(c.push(r.value),c.length!==e);u=!0);}catch(t){s=!0,o=t}finally{try{if(!u&&null!=n.return&&(a=n.return(),Object(a)!==a))return}finally{if(s)throw o}}return c}}(t,e)||function(t,e){if(t){if("string"==typeof t)return s(t,e);var n={}.toString.call(t).slice(8,-1);return"Object"===n&&t.constructor&&(n=t.constructor.name),"Map"===n||"Set"===n?Array.from(t):"Arguments"===n||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)?s(t,e):void 0}}(t,e)||function(){throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function s(t,e){(null==e||e>t.length)&&(e=t.length);for(var n=0,r=Array(e);n<e;n++)r[n]=t[n];return r}function l(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,f(r.key),r)}}function f(t){var e=function(t,e){if("object"!=c(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=c(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==c(e)?e:e+""}var y=function(){return t=function t(e){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.stages=e,this.start=null},(e=[{key:"begin",value:function(){this.start=new Date}},{key:"interval",value:function(){for(var t=new Date,e=Math.floor((t-this.start)/1e3),n=1e3,r=0,o=Object.entries(this.stages);r<o.length;r++){var i=u(o[r],2),a=i[0],c=i[1];if(!(e>=parseInt(a)))break;n=c}return n}}])&&l(t.prototype,e),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e}();const p=y;function h(t){return h="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},h(t)}function v(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,d(r.key),r)}}function b(t,e,n){return(e=d(e))in t?Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}):t[e]=n,t}function d(t){var e=function(t,e){if("object"!=h(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=h(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==h(e)?e:e+""}var m=function(){function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.intervalHanlder=new p({0:5e3,5:2e3,600:1e4,3600:0}),this.processing=!1,this.pairing=null,this.callbacks={}}return e=t,(n=[{key:"setPairing",value:function(t){this.pairing=t}},{key:"addCallBack",value:function(t,e){this.callbacks[t]=e}},{key:"start",value:function(){this.stopped=!1,this.intervalHanlder.begin(),this.onProcessing()}},{key:"stop",value:function(){this.stopped=!0}},{key:"onProcessing",value:function(){if(!this.processing&&!this.stopped){var t=this.intervalHanlder.interval();t>0&&setTimeout(this.check.bind(this),t)}}},{key:"onPaid",value:function(e){var n=this.callbacks[t.EVENT_PAID];n&&n(e)}},{key:"onCancelled",value:function(){var e=this.callbacks[t.EVENT_CANCELLED];e&&"function"==typeof e&&e()}},{key:"onFinish",value:function(t){return t.finish&&"PAID"===t.status?this.onPaid(t):t.finish&&"FAILED"===t.status?this.onCancelled():void 0}},{key:"check",value:function(){if(!this.stopped&&!this.processing){var t=this;this.processing=!0,a()({path:"/twint/v1/payment/status",method:"POST",data:{pairingId:this.pairing},cache:"no-store",parse:!1}).then((function(e){if(t.processing=!1,!e.ok)throw new Error("Network response was not ok");return e.json()})).then((function(e){if(!0===e.finish)return t.onFinish(e);t.onProcessing()})).catch((function(e){t.processing=!1,console.error("Error:",e)}))}}}])&&v(e.prototype,n),Object.defineProperty(e,"prototype",{writable:!1}),e;var e,n}();b(m,"EVENT_CANCELLED","cancelled"),b(m,"EVENT_PAID","paid");const g=m;function w(t){return w="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},w(t)}function E(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,k(r.key),r)}}function k(t){var e=function(t,e){if("object"!=w(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=w(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==w(e)?e:e+""}var S=function(){return t=function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t);var e="twint-copy-btn";this.input=document.getElementById("qr-token"),this.button=document.getElementById(e),this.button.addEventListener("click",this.onClick.bind(this)),this.clipboard=new Clipboard("#"+e),this.clipboard.on("success",this.onCopied.bind(this)),this.clipboard.on("error",this.onError.bind(this))},e=[{key:"onClick",value:function(t){t.preventDefault(),this.input.disabled=!1}},{key:"onCopied",value:function(t){t.clearSelection(),this.button.innerHTML=this.button.getAttribute("data-copied"),this.button.classList.add("copied"),this.button.classList.add("border-green-500"),this.button.classList.add("text-green-500"),this.input.disabled=!0,setTimeout(this.reset.bind(this),1e4)}},{key:"onError",value:function(t){console.error("Action:",t.action),console.error("Trigger:",t.trigger)}},{key:"reset",value:function(){this.button.innerHTML=this.button.getAttribute("data-default"),this.button.classList.remove("copied"),this.button.classList.remove("border-green-500"),this.button.classList.remove("text-green-500")}}],e&&E(t.prototype,e),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e}();const j=S;function P(t){return P="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},P(t)}function L(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,O(r.key),r)}}function O(t){var e=function(t,e){if("object"!=P(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=P(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==P(e)?e:e+""}var T=function(){return t=function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t)},(e=[{key:"init",value:function(){}},{key:"setToken",value:function(t){this.token=t}}])&&L(t.prototype,e),Object.defineProperty(t,"prototype",{writable:!1}),t;var t,e}();const _=T;function C(t){return C="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},C(t)}function x(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,A(r.key),r)}}function A(t){var e=function(t,e){if("object"!=C(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=C(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==C(e)?e:e+""}function B(t,e,n){return e=I(e),function(t,e){if(e&&("object"==C(e)||"function"==typeof e))return e;if(void 0!==e)throw new TypeError("Derived constructors may only return object or undefined");return function(t){if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}(t)}(t,R()?Reflect.construct(e,n||[],I(t).constructor):e.apply(t,n))}function R(){try{var t=!Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){})))}catch(t){}return(R=function(){return!!t})()}function I(t){return I=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(t){return t.__proto__||Object.getPrototypeOf(t)},I(t)}function N(t,e){return N=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,e){return t.__proto__=e,t},N(t,e)}var D=function(t){function e(){var t;return function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,e),(t=B(this,e)).button=document.getElementById("twint-addroid-button"),t}return function(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function");t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,writable:!0,configurable:!0}}),Object.defineProperty(t,"prototype",{writable:!1}),e&&N(t,e)}(e,t),n=e,(r=[{key:"init",value:function(){this.button&&(this.button.href=this.button.getAttribute("data-href").replace("--TOKEN--",this.token),this.button.click())}}])&&x(n.prototype,r),Object.defineProperty(n,"prototype",{writable:!1}),n;var n,r}(_);const M=D;function H(t){return H="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},H(t)}function F(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,G(r.key),r)}}function G(t){var e=function(t,e){if("object"!=H(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=H(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==H(e)?e:e+""}function Q(t,e,n){return e=q(e),function(t,e){if(e&&("object"==H(e)||"function"==typeof e))return e;if(void 0!==e)throw new TypeError("Derived constructors may only return object or undefined");return function(t){if(void 0===t)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return t}(t)}(t,V()?Reflect.construct(e,n||[],q(t).constructor):e.apply(t,n))}function V(){try{var t=!Boolean.prototype.valueOf.call(Reflect.construct(Boolean,[],(function(){})))}catch(t){}return(V=function(){return!!t})()}function q(t){return q=Object.setPrototypeOf?Object.getPrototypeOf.bind():function(t){return t.__proto__||Object.getPrototypeOf(t)},q(t)}function Y(t,e){return Y=Object.setPrototypeOf?Object.setPrototypeOf.bind():function(t,e){return t.__proto__=e,t},Y(t,e)}var K=function(t){function e(){var t;return function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,e),(t=Q(this,e)).container=document.getElementById("twint-ios-container"),t.registeredEvents=!1,t}return function(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function");t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,writable:!0,configurable:!0}}),Object.defineProperty(t,"prototype",{writable:!1}),e&&Y(t,e)}(e,t),n=e,(r=[{key:"init",value:function(){var t=this;this.container&&!this.registeredEvents&&(this.banks=this.container.querySelectorAll("img"),this.banks&&this.banks.forEach((function(e){e.addEventListener("touchend",(function(n){t.onClickedBank(n,e)}))})),this.appLinksElements=this.container.querySelector("select"),this.appLinksElements&&this.appLinksElements.addEventListener("change",this.onChangeAppList.bind(this)),this.registeredEvents=!0)}},{key:"onChangeAppList",value:function(t){var e=t.target,n=e.options[e.selectedIndex].value;this.openAppBank(n)}},{key:"onClickedBank",value:function(t,e){var n=e.getAttribute("data-link");this.openAppBank(n)}},{key:"openAppBank",value:function(t){if(t){t=t.replace("--TOKEN--",this.token);try{window.location.replace(t);var e=setInterval((function(){clearInterval(e)}),2e3)}catch(t){}}}}])&&F(n.prototype,r),Object.defineProperty(n,"prototype",{writable:!1}),n;var n,r}(_);const U=K;function W(t){return W="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},W(t)}function $(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,z(r.key),r)}}function z(t){var e=function(t,e){if("object"!=W(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=W(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==W(e)?e:e+""}var J,X,Z,tt=function(){function t(){!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.element=document.getElementById("twint-modal"),this.closeBtn=this.element.querySelector("#twint-close"),this.statusRefresher=new g,this.tokenCopier=new j,this.connectors=[],this.connectors.push(new M),this.connectors.push(new U),this.registerEvents(),this.callbacks={}}return e=t,n=[{key:"getData",value:function(){return{label:this.element.getAttribute("data-exist-label"),message:this.element.getAttribute("data-exist-message")}}},{key:"setContent",value:function(t){this.content=t}},{key:"addCallback",value:function(t,e){"function"==typeof e&&(this.callbacks[t]=e)}},{key:"show",value:function(){var t=this;this.closeBtn.querySelector("span").innerHTML=this.closeBtn.getAttribute("data-default"),this.tokenCopier.reset(),this.content.render(),this.element.classList.remove("!hidden"),this.connectors.forEach((function(e){e.setToken(t.content.token),e.init()})),this.statusRefresher.setPairing(this.content.pairing),this.statusRefresher.addCallBack(g.EVENT_CANCELLED,this.close.bind(this)),this.statusRefresher.addCallBack(g.EVENT_PAID,this.onPaid.bind(this)),this.statusRefresher.start()}},{key:"close",value:function(){this.element.classList.add("!hidden"),this.statusRefresher.stop();var e=this.callbacks[t.EVENT_CLOSED];e&&e()}},{key:"registerEvents",value:function(){this.closeBtn.addEventListener("click",this.close.bind(this))}},{key:"refreshMiniCart",value:function(){document.body.classList.contains("woocommerce-cart")||(jQuery(document.body).trigger("wc_fragment_refresh"),jQuery(document.body).trigger("added_to_cart"),jQuery(document.body).trigger("removed_from_cart"),jQuery(document.body).trigger("wc-blocks_removed_from_cart"),jQuery(document.body).trigger("wc-blocks_added_to_cart"))}},{key:"onPaid",value:function(t){this.refreshMiniCart(),this.closeBtn.querySelector("span").innerHTML=this.closeBtn.getAttribute("data-success"),location.href=t.extra.redirect}},{key:"continue",value:function(){this.closeBtn.innerHTML=this.closeBtn.getAttribute("data-success")}}],n&&$(e.prototype,n),Object.defineProperty(e,"prototype",{writable:!1}),e;var e,n}();J=tt,Z="CLOSED",(X=z(X="EVENT_CLOSED"))in J?Object.defineProperty(J,X,{value:Z,enumerable:!0,configurable:!0,writable:!0}):J[X]=Z;const et=tt;function nt(t){return nt="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},nt(t)}function rt(t,e){for(var n=0;n<e.length;n++){var r=e[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(t,ot(r.key),r)}}function ot(t){var e=function(t,e){if("object"!=nt(t)||!t)return t;var n=t[Symbol.toPrimitive];if(void 0!==n){var r=n.call(t,"string");if("object"!=nt(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return String(t)}(t);return"symbol"==nt(e)?e:e+""}var it=function(){return function(t,e,n){return e&&rt(t.prototype,e),Object.defineProperty(t,"prototype",{writable:!1}),t}((function t(e,n,r){var o=!(arguments.length>3&&void 0!==arguments[3])||arguments[3];!function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")}(this,t),this.token=e,this.amount=n,this.pairing=r,this.isExpress=o}),[{key:"render",value:function(){this.renderAmount(),this.renderToken(),this.renderQr(),this.adjustGuides()}},{key:"renderAmount",value:function(){var t=document.getElementById("twint-amount");t&&(t.innerHTML=this.amount)}},{key:"renderToken",value:function(){var t=document.getElementById("qr-token");t&&(t.value=this.token)}},{key:"renderQr",value:function(){var t=document.getElementById("qrcode");t.innerHTML="",new QRCode(t,{text:this.token,width:300,height:300,colorDark:"#000000",colorLight:"#ffffff",correctLevel:QRCode.CorrectLevel.H})}},{key:"adjustGuides",value:function(){var t=document.getElementById("twint-guide-contact"),e=t.parentElement;this.isExpress?(t.classList.remove("hidden"),e.classList.add("md:grid-cols-2")):(t.classList.add("hidden"),e.classList.remove("md:grid-cols-2"))}}])}();const at=it;function ct(t){return ct="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(t){return typeof t}:function(t){return t&&"function"==typeof Symbol&&t.constructor===Symbol&&t!==Symbol.prototype?"symbol":typeof t},ct(t)}function ut(){ut=function(){return e};var t,e={},n=Object.prototype,r=n.hasOwnProperty,o=Object.defineProperty||function(t,e,n){t[e]=n.value},i="function"==typeof Symbol?Symbol:{},a=i.iterator||"@@iterator",c=i.asyncIterator||"@@asyncIterator",u=i.toStringTag||"@@toStringTag";function s(t,e,n){return Object.defineProperty(t,e,{value:n,enumerable:!0,configurable:!0,writable:!0}),t[e]}try{s({},"")}catch(t){s=function(t,e,n){return t[e]=n}}function l(t,e,n,r){var i=e&&e.prototype instanceof d?e:d,a=Object.create(i.prototype),c=new C(r||[]);return o(a,"_invoke",{value:L(t,n,c)}),a}function f(t,e,n){try{return{type:"normal",arg:t.call(e,n)}}catch(t){return{type:"throw",arg:t}}}e.wrap=l;var y="suspendedStart",p="suspendedYield",h="executing",v="completed",b={};function d(){}function m(){}function g(){}var w={};s(w,a,(function(){return this}));var E=Object.getPrototypeOf,k=E&&E(E(x([])));k&&k!==n&&r.call(k,a)&&(w=k);var S=g.prototype=d.prototype=Object.create(w);function j(t){["next","throw","return"].forEach((function(e){s(t,e,(function(t){return this._invoke(e,t)}))}))}function P(t,e){function n(o,i,a,c){var u=f(t[o],t,i);if("throw"!==u.type){var s=u.arg,l=s.value;return l&&"object"==ct(l)&&r.call(l,"__await")?e.resolve(l.__await).then((function(t){n("next",t,a,c)}),(function(t){n("throw",t,a,c)})):e.resolve(l).then((function(t){s.value=t,a(s)}),(function(t){return n("throw",t,a,c)}))}c(u.arg)}var i;o(this,"_invoke",{value:function(t,r){function o(){return new e((function(e,o){n(t,r,e,o)}))}return i=i?i.then(o,o):o()}})}function L(e,n,r){var o=y;return function(i,a){if(o===h)throw Error("Generator is already running");if(o===v){if("throw"===i)throw a;return{value:t,done:!0}}for(r.method=i,r.arg=a;;){var c=r.delegate;if(c){var u=O(c,r);if(u){if(u===b)continue;return u}}if("next"===r.method)r.sent=r._sent=r.arg;else if("throw"===r.method){if(o===y)throw o=v,r.arg;r.dispatchException(r.arg)}else"return"===r.method&&r.abrupt("return",r.arg);o=h;var s=f(e,n,r);if("normal"===s.type){if(o=r.done?v:p,s.arg===b)continue;return{value:s.arg,done:r.done}}"throw"===s.type&&(o=v,r.method="throw",r.arg=s.arg)}}}function O(e,n){var r=n.method,o=e.iterator[r];if(o===t)return n.delegate=null,"throw"===r&&e.iterator.return&&(n.method="return",n.arg=t,O(e,n),"throw"===n.method)||"return"!==r&&(n.method="throw",n.arg=new TypeError("The iterator does not provide a '"+r+"' method")),b;var i=f(o,e.iterator,n.arg);if("throw"===i.type)return n.method="throw",n.arg=i.arg,n.delegate=null,b;var a=i.arg;return a?a.done?(n[e.resultName]=a.value,n.next=e.nextLoc,"return"!==n.method&&(n.method="next",n.arg=t),n.delegate=null,b):a:(n.method="throw",n.arg=new TypeError("iterator result is not an object"),n.delegate=null,b)}function T(t){var e={tryLoc:t[0]};1 in t&&(e.catchLoc=t[1]),2 in t&&(e.finallyLoc=t[2],e.afterLoc=t[3]),this.tryEntries.push(e)}function _(t){var e=t.completion||{};e.type="normal",delete e.arg,t.completion=e}function C(t){this.tryEntries=[{tryLoc:"root"}],t.forEach(T,this),this.reset(!0)}function x(e){if(e||""===e){var n=e[a];if(n)return n.call(e);if("function"==typeof e.next)return e;if(!isNaN(e.length)){var o=-1,i=function n(){for(;++o<e.length;)if(r.call(e,o))return n.value=e[o],n.done=!1,n;return n.value=t,n.done=!0,n};return i.next=i}}throw new TypeError(ct(e)+" is not iterable")}return m.prototype=g,o(S,"constructor",{value:g,configurable:!0}),o(g,"constructor",{value:m,configurable:!0}),m.displayName=s(g,u,"GeneratorFunction"),e.isGeneratorFunction=function(t){var e="function"==typeof t&&t.constructor;return!!e&&(e===m||"GeneratorFunction"===(e.displayName||e.name))},e.mark=function(t){return Object.setPrototypeOf?Object.setPrototypeOf(t,g):(t.__proto__=g,s(t,u,"GeneratorFunction")),t.prototype=Object.create(S),t},e.awrap=function(t){return{__await:t}},j(P.prototype),s(P.prototype,c,(function(){return this})),e.AsyncIterator=P,e.async=function(t,n,r,o,i){void 0===i&&(i=Promise);var a=new P(l(t,n,r,o),i);return e.isGeneratorFunction(n)?a:a.next().then((function(t){return t.done?t.value:a.next()}))},j(S),s(S,u,"Generator"),s(S,a,(function(){return this})),s(S,"toString",(function(){return"[object Generator]"})),e.keys=function(t){var e=Object(t),n=[];for(var r in e)n.push(r);return n.reverse(),function t(){for(;n.length;){var r=n.pop();if(r in e)return t.value=r,t.done=!1,t}return t.done=!0,t}},e.values=x,C.prototype={constructor:C,reset:function(e){if(this.prev=0,this.next=0,this.sent=this._sent=t,this.done=!1,this.delegate=null,this.method="next",this.arg=t,this.tryEntries.forEach(_),!e)for(var n in this)"t"===n.charAt(0)&&r.call(this,n)&&!isNaN(+n.slice(1))&&(this[n]=t)},stop:function(){this.done=!0;var t=this.tryEntries[0].completion;if("throw"===t.type)throw t.arg;return this.rval},dispatchException:function(e){if(this.done)throw e;var n=this;function o(r,o){return c.type="throw",c.arg=e,n.next=r,o&&(n.method="next",n.arg=t),!!o}for(var i=this.tryEntries.length-1;i>=0;--i){var a=this.tryEntries[i],c=a.completion;if("root"===a.tryLoc)return o("end");if(a.tryLoc<=this.prev){var u=r.call(a,"catchLoc"),s=r.call(a,"finallyLoc");if(u&&s){if(this.prev<a.catchLoc)return o(a.catchLoc,!0);if(this.prev<a.finallyLoc)return o(a.finallyLoc)}else if(u){if(this.prev<a.catchLoc)return o(a.catchLoc,!0)}else{if(!s)throw Error("try statement without catch or finally");if(this.prev<a.finallyLoc)return o(a.finallyLoc)}}}},abrupt:function(t,e){for(var n=this.tryEntries.length-1;n>=0;--n){var o=this.tryEntries[n];if(o.tryLoc<=this.prev&&r.call(o,"finallyLoc")&&this.prev<o.finallyLoc){var i=o;break}}i&&("break"===t||"continue"===t)&&i.tryLoc<=e&&e<=i.finallyLoc&&(i=null);var a=i?i.completion:{};return a.type=t,a.arg=e,i?(this.method="next",this.next=i.finallyLoc,b):this.complete(a)},complete:function(t,e){if("throw"===t.type)throw t.arg;return"break"===t.type||"continue"===t.type?this.next=t.arg:"return"===t.type?(this.rval=this.arg=t.arg,this.method="return",this.next="end"):"normal"===t.type&&e&&(this.next=e),b},finish:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var n=this.tryEntries[e];if(n.finallyLoc===t)return this.complete(n.completion,n.afterLoc),_(n),b}},catch:function(t){for(var e=this.tryEntries.length-1;e>=0;--e){var n=this.tryEntries[e];if(n.tryLoc===t){var r=n.completion;if("throw"===r.type){var o=r.arg;_(n)}return o}}throw Error("illegal catch attempt")},delegateYield:function(e,n,r){return this.delegate={iterator:x(e),resultName:n,nextLoc:r},"next"===this.method&&(this.arg=t),b}},e}function st(t,e,n,r,o,i,a){try{var c=t[i](a),u=c.value}catch(t){return void n(t)}c.done?e(u):Promise.resolve(u).then(r,o)}var lt=(0,r.getSetting)("twint_regular_data",{}),ft=(0,e.__)("TWINT","woocommerce-gateway-twint"),yt=function(t){var e=t.eventRegistration,n=t.emitResponse,r=e.onCheckoutAfterProcessingWithSuccess;return(0,o.useEffect)((function(){var t=r(function(){var t,e=(t=ut().mark((function t(e){var n,r,o;return ut().wrap((function(t){for(;;)switch(t.prev=t.next){case 0:n=e.processingResponse,"success"===(r=n.paymentDetails).result&&((o=new et).addCallback(et.EVENT_CLOSED,(function(){location.reload()})),o.setContent(new at(r.pairingToken,r.amount+r.currency,r.pairingId,!1)),o.show());case 3:case"end":return t.stop()}}),t)})),function(){var e=this,n=arguments;return new Promise((function(r,o){var i=t.apply(e,n);function a(t){st(i,r,o,a,c,"next",t)}function c(t){st(i,r,o,a,c,"throw",t)}a(void 0)}))});return function(t){return e.apply(this,arguments)}}());return function(){return t()}}),[n.noticeContexts.PAYMENTS,n.responseTypes.ERROR,n.responseTypes.SUCCESS]),React.createElement(React.Fragment,null)},pt=function(){return React.createElement("div",{onClick:function(t,e){return!1}})},ht=function(t){var e=t.components.PaymentMethodLabel;return React.createElement(e,{text:ft})},vt={name:"twint_regular",label:React.createElement(ht,null),content:React.createElement(yt,null),edit:React.createElement(pt,null),canMakePayment:function(){return!0},ariaLabel:ft,supports:{features:lt.supports}};(0,n.registerPaymentMethod)(vt)})();