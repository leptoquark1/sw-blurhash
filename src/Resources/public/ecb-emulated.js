!function(){"use strict";var t=3294.6,e=269.025,n=Math.PI,o=2*Math.PI;function r(t){return(t<0?-1:1)*t*t}function a(t){for(t+=n/2;t>n;)t-=o;var e=1.27323954*t-.405284735*r(t);return.225*(r(e)-e)+e}function i(t,e,n){for(var o=0;e<n;)o*=83,o+="0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz#$%*+,-.:;=?@[]^_{|}~".indexOf(t[e++]);return o}function u(n){return n>10.31475?Math.pow(n/e+.052132,2.4):n/t}function d(n){return~~(n>1227e-8?e*Math.pow(n,.416666)-13.025:n*t+1)}function c(t,e,o){var c=arguments.length>3&&void 0!==arguments[3]?arguments[3]:1,s=i(t,0,1),h=s%9+1,l=1+~~(s/9),f=h*l,m=(i(t,1,2)+1)/13446*c,v=new Float64Array(3*f),w=i(t,2,6);v[0]=u(w>>16),v[1]=u(w>>8&255),v[2]=u(255&w);var b=0;for(b=1;b<f;b++)w=i(t,4+2*b,6+2*b),v[3*b]=r(~~(w/361)-9)*m,v[3*b+1]=r(~~(w/19)%19-9)*m,v[3*b+2]=r(w%19-9)*m;for(var g=4*e,p=new Uint8ClampedArray(g*o),y=0;y<o;y++)for(var N=n*y/o,E=0;E<e;E++){for(var A=0,O=0,L=0,M=n*E/e,I=0;I<l;I++){var j=a(N*I);for(b=0;b<h;b++){var C=a(M*b)*j,R=3*(b+I*h);A+=v[R]*C,O+=v[R+1]*C,L+=v[R+2]*C}}var S=4*E+y*g;p[S]=d(A),p[S+1]=d(O),p[S+2]=d(L),p[S+3]=255}return p}function s(t,e,n,o){var r=function(t,e,n){var o=window.document.createElement("canvas");return o.width=e,o.height=n,o.getContext("2d").putImageData(new ImageData(t,e,n),0,0),o}(t,e,n);r.toBlob((function(t){if(null===t)return o(null);var e=window.document.createElement("img"),n=URL.createObjectURL(t);e.onload=function(){URL.revokeObjectURL(n)},o(n)}))}function h(t){var e=t.getBoundingClientRect(),n={height:window.innerHeight||window.document.documentElement.clientHeight,width:window.innerWidth||window.document.documentElement.clientWidth};return e.top>0&&e.bottom>0||e.bottom-n.height>0&&e.top<0||-1*(e.top-n.height)>0&&e.bottom-n.height>0||-1*(e.bottom-n.height)>0&&e.top+n.height<0}function l(t,e){var n=t.attributes.getNamedItem(e);return n?n.value:null}!function(){var t=[],e=new Object(null),n=new Object(null),o=new Object(null);function r(t){return t.srcset+t.node.sizes}function a(t){t.node.srcset=t.srcset,t.node.src=l(t.node,"data-src"),t.node.removeAttribute("data-srcset"),t.node.removeAttribute("data-src")}function i(t){return function(){this.onload=null,t.node.parentElement.classList.remove("ecb-loading"),this.removeAttribute("data-ecb-bh"),this.removeAttribute("data-ow"),this.removeAttribute("data-oh")}}function u(t,n){t.node.onload=function(t){return function(){this.setAttribute("data-ecb-bh","1"),this.onload=i(t);var n=r(t);e.hasOwnProperty(n)&&e[n].image.complete&&!this.srcset&&(this.srcset=t.srcset)}}(t),t.node.src=n,t.node.parentElement.classList.add("ecb-loading")}function d(t){return function(e){if(Array.isArray(n[t.hash])){n[t.hash]=e;for(var r=o[t.hash]||[];r.length;)r.pop()(e)}null!==e?u(t,e):l(t.node,"data-blurhash")&&i(t).call(t.node)}}function f(t){var i=n[t.hash],u=function(t){var n=r(t);if(e.hasOwnProperty(n)){var o=e[n].image;if(e[n].node.isEqualNode(t.node))return o;if(o.complete)a(t);else{var i=o.onload;o.onload=function(){i.call(this),a(t)}}return o}var u=new Image(t.node.width,t.node.height);return u.onload=function(){u.onload=null,a(t)},u.sizes=t.node.sizes,u.srcset=t.srcset,e[n]={image:u,node:t.node},u}(t);u.complete||(null===i||"string"==typeof i?d(t)(i):Array.isArray(i)&&!1===i.includes(t.node)?(i.push(t.node),!1===Array.isArray(o[t.hash])&&(o[t.hash]=[]),o[t.hash].push(d(t))):(n[t.hash]=[t.node],function(t,e,n){var o=arguments.length>3&&void 0!==arguments[3]?arguments[3]:200;e=e||t.hash,setTimeout((function(){!1===e.complete?s(c(t.hash,t.width,t.height),t.width,t.height,(function(t){!0===e.complete?n(null):n(t)})):n(null)}),o)}(t,u,d(t),50)))}function m(e){var n=function(t){var e=l(t,"data-blurhash"),n=l(t,"data-src");if(!e)return null;var o=Number(l(t,"data-ow")),r=Number(l(t,"data-oh")),a=l(t,"srcset")||l(t,"data-srcset");return isNaN(o)||0===o||isNaN(r)||0===r?(n&&(t.src=n),null):{node:t,hash:e,width:o,height:r,srcset:a}}(e);if(n&&n.hash){if(function(t){return t.parentElement&&t.parentElement.classList.contains("image-zoom-container")||t.classList.contains("js-load-img")}(n.node))return a(n),void i(n).call(n.node);n.node.onload=function(){this.onload=i(n)},function(t){var e=!0;do{var n=getComputedStyle(t);e=!(0===window.Number(n.opacity)||"none"===n.display),t=t.parentElement}while(null!==t&&!0===e);return e}(n.node)&&h(n.node)?f(n):function(e){"complete"===window.document.readyState?f(e):t.unshift(e)}(n)}}function v(t){t.forEach((function(t){if(function(t){return t.nodeType===Node.ELEMENT_NODE&&"IMG"===t.tagName&&!l(t,"data-ecb-bh")}(t))return m(t);"complete"===window.document.readyState&&t.hasChildNodes()&&v(t.childNodes)}))}function w(t){setTimeout((function(){for(var e=0;e<t.length;e++)v(t[e].addedNodes)}),1)}function b(){for(;t.length;)f(t.pop());var e;(e=w,MutationObserver=window.MutationObserver||window.WebKitMutationObserver,new MutationObserver(e)).observe(document.body,{childList:!0,subtree:!0,attributeFilter:["data-blurhash"]})}window.ecbDecode=function(){var t=window.document.getElementsByTagName("img");if(!(t.length<=0))for(var e=0;e<t.length;e++){m(t.item(e))}},window.ecbReadyStateChangeListener=function(){"interactive"===window.document.readyState&&b()}}()}();
