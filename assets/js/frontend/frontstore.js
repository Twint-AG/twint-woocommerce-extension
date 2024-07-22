(()=>{var t={182:t=>{t.exports=function t(e,n,i){function o(s,c){if(!n[s]){if(!e[s]){if(r)return r(s,!0);var a=new Error("Cannot find module '"+s+"'");throw a.code="MODULE_NOT_FOUND",a}var l=n[s]={exports:{}};e[s][0].call(l.exports,(function(t){return o(e[s][1][t]||t)}),l,l.exports,t,e,n,i)}return n[s].exports}for(var r=void 0,s=0;s<i.length;s++)o(i[s]);return o}({1:[function(t,e,n){var i=t("closest"),o=t("component-event"),r=["focus","blur"];n.bind=function(t,e,n,s,c){return-1!==r.indexOf(n)&&(c=!0),o.bind(t,n,(function(n){var o=n.target||n.srcElement;n.delegateTarget=i(o,e,!0,t),n.delegateTarget&&s.call(t,n)}),c)},n.unbind=function(t,e,n,i){-1!==r.indexOf(e)&&(i=!0),o.unbind(t,e,n,i)}},{closest:2,"component-event":4}],2:[function(t,e,n){var i=t("matches-selector");e.exports=function(t,e,n){for(var o=n?t:t.parentNode;o&&o!==document;){if(i(o,e))return o;o=o.parentNode}}},{"matches-selector":3}],3:[function(t,e,n){var i=Element.prototype,o=i.matchesSelector||i.webkitMatchesSelector||i.mozMatchesSelector||i.msMatchesSelector||i.oMatchesSelector;e.exports=function(t,e){if(o)return o.call(t,e);for(var n=t.parentNode.querySelectorAll(e),i=0;i<n.length;++i)if(n[i]==t)return!0;return!1}},{}],4:[function(t,e,n){var i=window.addEventListener?"addEventListener":"attachEvent",o=window.removeEventListener?"removeEventListener":"detachEvent",r="addEventListener"!==i?"on":"";n.bind=function(t,e,n,o){return t[i](r+e,n,o||!1),n},n.unbind=function(t,e,n,i){return t[o](r+e,n,i||!1),n}},{}],5:[function(t,e,n){function i(){}i.prototype={on:function(t,e,n){var i=this.e||(this.e={});return(i[t]||(i[t]=[])).push({fn:e,ctx:n}),this},once:function(t,e,n){var i=this,o=function(){i.off(t,o),e.apply(n,arguments)};return this.on(t,o,n)},emit:function(t){for(var e=[].slice.call(arguments,1),n=((this.e||(this.e={}))[t]||[]).slice(),i=0,o=n.length;i<o;i++)n[i].fn.apply(n[i].ctx,e);return this},off:function(t,e){var n=this.e||(this.e={}),i=n[t],o=[];if(i&&e)for(var r=0,s=i.length;r<s;r++)i[r].fn!==e&&o.push(i[r]);return o.length?n[t]=o:delete n[t],this}},e.exports=i},{}],6:[function(t,e,n){"use strict";n.__esModule=!0;var i=function(){function t(t,e){for(var n=0;n<e.length;n++){var i=e[n];i.enumerable=i.enumerable||!1,i.configurable=!0,"value"in i&&(i.writable=!0),Object.defineProperty(t,i.key,i)}}return function(e,n,i){return n&&t(e.prototype,n),i&&t(e,i),e}}();var o=function(){function t(e){(function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")})(this,t),this.resolveOptions(e),this.initSelection()}return t.prototype.resolveOptions=function(){var t=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action=t.action,this.emitter=t.emitter,this.target=t.target,this.text=t.text,this.trigger=t.trigger,this.selectedText=""},t.prototype.initSelection=function(){if(this.text&&this.target)throw new Error('Multiple attributes declared, use either "target" or "text"');if(this.text)this.selectFake();else{if(!this.target)throw new Error('Missing required attributes, use either "target" or "text"');this.selectTarget()}},t.prototype.selectFake=function(){var t=this;this.removeFake(),this.fakeHandler=document.body.addEventListener("click",(function(){return t.removeFake()})),this.fakeElem=document.createElement("textarea"),this.fakeElem.style.position="absolute",this.fakeElem.style.left="-9999px",this.fakeElem.style.top=document.body.scrollTop+"px",this.fakeElem.setAttribute("readonly",""),this.fakeElem.value=this.text,this.selectedText=this.text,document.body.appendChild(this.fakeElem),this.fakeElem.select(),this.copyText()},t.prototype.removeFake=function(){this.fakeHandler&&(document.body.removeEventListener("click"),this.fakeHandler=null),this.fakeElem&&(document.body.removeChild(this.fakeElem),this.fakeElem=null)},t.prototype.selectTarget=function(){if("INPUT"===this.target.nodeName||"TEXTAREA"===this.target.nodeName)this.target.select(),this.selectedText=this.target.value;else{var t=document.createRange(),e=window.getSelection();t.selectNodeContents(this.target),e.addRange(t),this.selectedText=e.toString()}this.copyText()},t.prototype.copyText=function(){var t=void 0;try{t=document.execCommand(this.action)}catch(e){t=!1}this.handleResult(t)},t.prototype.handleResult=function(t){t?this.emitter.emit("success",{action:this.action,text:this.selectedText,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)}):this.emitter.emit("error",{action:this.action,trigger:this.trigger,clearSelection:this.clearSelection.bind(this)})},t.prototype.clearSelection=function(){this.target&&this.target.blur(),window.getSelection().removeAllRanges()},t.prototype.destroy=function(){this.removeFake()},i(t,[{key:"action",set:function(){var t=arguments.length<=0||void 0===arguments[0]?"copy":arguments[0];if(this._action=t,"copy"!==this._action&&"cut"!==this._action)throw new Error('Invalid "action" value, use either "copy" or "cut"')},get:function(){return this._action}},{key:"target",set:function(t){if(void 0!==t){if(!t||"object"!=typeof t||1!==t.nodeType)throw new Error('Invalid "target" value, use a valid Element');this._target=t}},get:function(){return this._target}}]),t}();n.default=o,e.exports=n.default},{}],7:[function(t,e,n){"use strict";function i(t){return t&&t.__esModule?t:{default:t}}n.__esModule=!0;var o=i(t("./clipboard-action")),r=i(t("delegate-events")),s=function(t){function e(n,i){(function(t,e){if(!(t instanceof e))throw new TypeError("Cannot call a class as a function")})(this,e),t.call(this),this.resolveOptions(i),this.delegateClick(n)}return function(t,e){if("function"!=typeof e&&null!==e)throw new TypeError("Super expression must either be null or a function, not "+typeof e);t.prototype=Object.create(e&&e.prototype,{constructor:{value:t,enumerable:!1,writable:!0,configurable:!0}}),e&&(Object.setPrototypeOf?Object.setPrototypeOf(t,e):t.__proto__=e)}(e,t),e.prototype.resolveOptions=function(){var t=arguments.length<=0||void 0===arguments[0]?{}:arguments[0];this.action="function"==typeof t.action?t.action:this.defaultAction,this.target="function"==typeof t.target?t.target:this.defaultTarget,this.text="function"==typeof t.text?t.text:this.defaultText},e.prototype.delegateClick=function(t){var e=this;this.binding=r.default.bind(document.body,t,"click",(function(t){return e.onClick(t)}))},e.prototype.undelegateClick=function(){r.default.unbind(document.body,"click",this.binding)},e.prototype.onClick=function(t){this.clipboardAction&&(this.clipboardAction=null),this.clipboardAction=new o.default({action:this.action(t.delegateTarget),target:this.target(t.delegateTarget),text:this.text(t.delegateTarget),trigger:t.delegateTarget,emitter:this})},e.prototype.defaultAction=function(t){return c("action",t)},e.prototype.defaultTarget=function(t){var e=c("target",t);if(e)return document.querySelector(e)},e.prototype.defaultText=function(t){return c("text",t)},e.prototype.destroy=function(){this.undelegateClick(),this.clipboardAction&&(this.clipboardAction.destroy(),this.clipboardAction=null)},e}(i(t("tiny-emitter")).default);function c(t,e){var n="data-clipboard-"+t;if(e.hasAttribute(n))return e.getAttribute(n)}n.default=s,e.exports=n.default},{"./clipboard-action":6,"delegate-events":1,"tiny-emitter":5}]},{},[7])(7)}},e={};function n(i){var o=e[i];if(void 0!==o)return o.exports;var r=e[i]={exports:{}};return t[i](r,r.exports,n),r.exports}n.n=t=>{var e=t&&t.__esModule?()=>t.default:()=>t;return n.d(e,{a:e}),e},n.d=(t,e)=>{for(var i in e)n.o(e,i)&&!n.o(t,i)&&Object.defineProperty(t,i,{enumerable:!0,get:e[i]})},n.o=(t,e)=>Object.prototype.hasOwnProperty.call(t,e),(()=>{"use strict";var t=n(182),e=n.n(t);document.addEventListener("DOMContentLoaded",(function(t){(new class{constructor(){this.options={selector:"#btn-copy-token",target:"#qr-token"}}init(){this.input=document.querySelector(this.options.target),this.button=document.querySelector(this.options.selector),this.button&&(this.button.addEventListener("click",this.onClick.bind(this)),this.clipboard=new(e())(this.options.selector),this.clipboard.on("success",this.onCopied.bind(this)),this.clipboard.on("error",this.onError.bind(this)))}btnOriginalText(){return this.button.textContent}onClick(t){t.preventDefault(),this.input.disabled=!1}onCopied(t){t.clearSelection();const e=this.btnOriginalText();this.button.innerHTML="Copied!",this.button?.classList?.add("copied"),this.input.disabled=!0,setTimeout((()=>{this.button.innerHTML=e,this.button?.classList?.remove("copied")}),2e3)}onError(t){console.error("Action:",t.action),console.error("Trigger:",t.trigger)}}).init()}))})()})();