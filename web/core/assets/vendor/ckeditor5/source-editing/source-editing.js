!function(e){const t=e.en=e.en||{};t.dictionary=Object.assign(t.dictionary||{},{Source:"Source"})}(window.CKEDITOR_TRANSLATIONS||(window.CKEDITOR_TRANSLATIONS={})),
/*!
 * @license Copyright (c) 2003-2022, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see LICENSE.md.
 */(()=>{var e={821:(e,t,i)=>{"use strict";i.d(t,{Z:()=>r});var n=i(609),o=i.n(n)()((function(e){return e[1]}));o.push([e.id,'.ck-source-editing-area{overflow:hidden;position:relative}.ck-source-editing-area textarea,.ck-source-editing-area:after{border:1px solid transparent;font-family:monospace;font-size:var(--ck-font-size-normal);line-height:var(--ck-line-height-base);margin:0;padding:var(--ck-spacing-large);white-space:pre-wrap}.ck-source-editing-area:after{content:attr(data-value) " ";display:block;visibility:hidden}.ck-source-editing-area textarea{border-color:var(--ck-color-base-border);border-radius:0;box-sizing:border-box;height:100%;outline:none;overflow:hidden;position:absolute;resize:none;width:100%}.ck-rounded-corners .ck-source-editing-area textarea,.ck-source-editing-area textarea.ck-rounded-corners{border-radius:var(--ck-border-radius);border-top-left-radius:0;border-top-right-radius:0}.ck-source-editing-area textarea:not([readonly]):focus{border:var(--ck-focus-ring);box-shadow:var(--ck-inner-shadow),0 0;outline:none}',""]);const r=o},609:e=>{"use strict";e.exports=function(e){var t=[];return t.toString=function(){return this.map((function(t){var i=e(t);return t[2]?"@media ".concat(t[2]," {").concat(i,"}"):i})).join("")},t.i=function(e,i,n){"string"==typeof e&&(e=[[null,e,""]]);var o={};if(n)for(var r=0;r<this.length;r++){var a=this[r][0];null!=a&&(o[a]=!0)}for(var s=0;s<e.length;s++){var d=[].concat(e[s]);n&&o[d[0]]||(i&&(d[2]?d[2]="".concat(i," and ").concat(d[2]):d[2]=i),t.push(d))}},t}},62:(e,t,i)=>{"use strict";var n,o=function(){return void 0===n&&(n=Boolean(window&&document&&document.all&&!window.atob)),n},r=function(){var e={};return function(t){if(void 0===e[t]){var i=document.querySelector(t);if(window.HTMLIFrameElement&&i instanceof window.HTMLIFrameElement)try{i=i.contentDocument.head}catch(e){i=null}e[t]=i}return e[t]}}(),a=[];function s(e){for(var t=-1,i=0;i<a.length;i++)if(a[i].identifier===e){t=i;break}return t}function d(e,t){for(var i={},n=[],o=0;o<e.length;o++){var r=e[o],d=t.base?r[0]+t.base:r[0],c=i[d]||0,l="".concat(d," ").concat(c);i[d]=c+1;var u=s(l),h={css:r[1],media:r[2],sourceMap:r[3]};-1!==u?(a[u].references++,a[u].updater(h)):a.push({identifier:l,updater:p(h,t),references:1}),n.push(l)}return n}function c(e){var t=document.createElement("style"),n=e.attributes||{};if(void 0===n.nonce){var o=i.nc;o&&(n.nonce=o)}if(Object.keys(n).forEach((function(e){t.setAttribute(e,n[e])})),"function"==typeof e.insert)e.insert(t);else{var a=r(e.insert||"head");if(!a)throw new Error("Couldn't find a style target. This probably means that the value for the 'insert' parameter is invalid.");a.appendChild(t)}return t}var l,u=(l=[],function(e,t){return l[e]=t,l.filter(Boolean).join("\n")});function h(e,t,i,n){var o=i?"":n.media?"@media ".concat(n.media," {").concat(n.css,"}"):n.css;if(e.styleSheet)e.styleSheet.cssText=u(t,o);else{var r=document.createTextNode(o),a=e.childNodes;a[t]&&e.removeChild(a[t]),a.length?e.insertBefore(r,a[t]):e.appendChild(r)}}function m(e,t,i){var n=i.css,o=i.media,r=i.sourceMap;if(o?e.setAttribute("media",o):e.removeAttribute("media"),r&&"undefined"!=typeof btoa&&(n+="\n/*# sourceMappingURL=data:application/json;base64,".concat(btoa(unescape(encodeURIComponent(JSON.stringify(r))))," */")),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}var f=null,g=0;function p(e,t){var i,n,o;if(t.singleton){var r=g++;i=f||(f=c(t)),n=h.bind(null,i,r,!1),o=h.bind(null,i,r,!0)}else i=c(t),n=m.bind(null,i,t),o=function(){!function(e){if(null===e.parentNode)return!1;e.parentNode.removeChild(e)}(i)};return n(e),function(t){if(t){if(t.css===e.css&&t.media===e.media&&t.sourceMap===e.sourceMap)return;n(e=t)}else o()}}e.exports=function(e,t){(t=t||{}).singleton||"boolean"==typeof t.singleton||(t.singleton=o());var i=d(e=e||[],t);return function(e){if(e=e||[],"[object Array]"===Object.prototype.toString.call(e)){for(var n=0;n<i.length;n++){var o=s(i[n]);a[o].references--}for(var r=d(e,t),c=0;c<i.length;c++){var l=s(i[c]);0===a[l].references&&(a[l].updater(),a.splice(l,1))}i=r}}}},704:(e,t,i)=>{e.exports=i(79)("./src/core.js")},273:(e,t,i)=>{e.exports=i(79)("./src/ui.js")},209:(e,t,i)=>{e.exports=i(79)("./src/utils.js")},79:e=>{"use strict";e.exports=CKEditor5.dll}},t={};function i(n){var o=t[n];if(void 0!==o)return o.exports;var r=t[n]={id:n,exports:{}};return e[n](r,r.exports,i),r.exports}i.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return i.d(t,{a:t}),t},i.d=(e,t)=>{for(var n in t)i.o(t,n)&&!i.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},i.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),i.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},i.nc=void 0;var n={};(()=>{"use strict";i.r(n),i.d(n,{SourceEditing:()=>h});var e=i(704),t=i(273),o=i(209);function r(e){const t=[{name:"address",isVoid:!1},{name:"article",isVoid:!1},{name:"aside",isVoid:!1},{name:"blockquote",isVoid:!1},{name:"br",isVoid:!0},{name:"details",isVoid:!1},{name:"dialog",isVoid:!1},{name:"dd",isVoid:!1},{name:"div",isVoid:!1},{name:"dl",isVoid:!1},{name:"dt",isVoid:!1},{name:"fieldset",isVoid:!1},{name:"figcaption",isVoid:!1},{name:"figure",isVoid:!1},{name:"footer",isVoid:!1},{name:"form",isVoid:!1},{name:"h1",isVoid:!1},{name:"h2",isVoid:!1},{name:"h3",isVoid:!1},{name:"h4",isVoid:!1},{name:"h5",isVoid:!1},{name:"h6",isVoid:!1},{name:"header",isVoid:!1},{name:"hgroup",isVoid:!1},{name:"hr",isVoid:!0},{name:"input",isVoid:!0},{name:"li",isVoid:!1},{name:"main",isVoid:!1},{name:"nav",isVoid:!1},{name:"ol",isVoid:!1},{name:"p",isVoid:!1},{name:"section",isVoid:!1},{name:"table",isVoid:!1},{name:"tbody",isVoid:!1},{name:"td",isVoid:!1},{name:"textarea",isVoid:!1},{name:"th",isVoid:!1},{name:"thead",isVoid:!1},{name:"tr",isVoid:!1},{name:"ul",isVoid:!1}],i=t.map((e=>e.name)).join("|"),n=e.replace(new RegExp(`</?(${i})( .*?)?>`,"g"),"\n$&\n").split("\n");let o=0;return n.filter((e=>e.length)).map((e=>function(e,t){return t.some((t=>!t.isVoid&&!!new RegExp(`<${t.name}( .*?)?>`).test(e)))}(e,t)?a(e,o++):function(e,t){return t.some((t=>new RegExp(`</${t.name}>`).test(e)))}(e,t)?a(e,--o):a(e,o))).join("\n")}function a(e,t,i="    "){return`${i.repeat(Math.max(0,t))}${e}`}var s=i(62),d=i.n(s),c=i(821),l={injectType:"singletonStyleTag",attributes:{"data-cke":!0},insert:"head",singleton:!0};d()(c.Z,l);c.Z.locals;const u="SourceEditingMode";class h extends e.Plugin{static get pluginName(){return"SourceEditing"}static get requires(){return[e.PendingActions]}constructor(e){super(e),this.set("isSourceEditingMode",!1),this._elementReplacer=new o.ElementReplacer,this._replacedRoots=new Map,this._dataFromRoots=new Map}init(){const i=this.editor,n=i.t;i.ui.componentFactory.add("sourceEditing",(o=>{const r=new t.ButtonView(o);return r.set({label:n("Source"),icon:'<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="m12.5 0 5 4.5v15.003h-16V0h11zM3 1.5v3.25l-1.497 1-.003 8 1.5 1v3.254L7.685 18l-.001 1.504H17.5V8.002L16 9.428l-.004-4.22-4.222-3.692L3 1.5z"/><path d="M4.06 6.64a.75.75 0 0 1 .958 1.15l-.085.07L2.29 9.75l2.646 1.89c.302.216.4.62.232.951l-.058.095a.75.75 0 0 1-.951.232l-.095-.058-3.5-2.5V9.14l3.496-2.5zm4.194 6.22a.75.75 0 0 1-.958-1.149l.085-.07 2.643-1.89-2.646-1.89a.75.75 0 0 1-.232-.952l.058-.095a.75.75 0 0 1 .95-.232l.096.058 3.5 2.5v1.22l-3.496 2.5zm7.644-.836 2.122 2.122-5.825 5.809-2.125-.005.003-2.116zm2.539-1.847 1.414 1.414a.5.5 0 0 1 0 .707l-1.06 1.06-2.122-2.12 1.061-1.061a.5.5 0 0 1 .707 0z"/></svg>',tooltip:!0,withText:!0,class:"ck-source-editing-button"}),r.bind("isOn").to(this,"isSourceEditingMode"),r.bind("isEnabled").to(this,"isEnabled",i,"isReadOnly",i.plugins.get(e.PendingActions),"hasAny",((e,t,i)=>!!e&&(!t&&!i))),this.listenTo(r,"execute",(()=>{this.isSourceEditingMode=!this.isSourceEditingMode})),r})),this._isAllowedToHandleSourceEditingMode()&&(this.on("change:isSourceEditingMode",((e,t,i)=>{i?(this._showSourceEditing(),this._disableCommands()):(this._hideSourceEditing(),this._enableCommands())})),this.on("change:isEnabled",((e,t,i)=>this._handleReadOnlyMode(!i))),this.listenTo(i,"change:isReadOnly",((e,t,i)=>this._handleReadOnlyMode(i)))),i.data.on("get",(()=>{this.isSourceEditingMode&&this._updateEditorData()}),{priority:"high"})}afterInit(){const e=this.editor;["RealTimeCollaborativeEditing","CommentsEditing","TrackChangesEditing","RevisionHistory"].some((t=>e.plugins.has(t)))&&console.warn("You initialized the editor with the source editing feature and at least one of the collaboration features. Please be advised that the source editing feature may not work, and be careful when editing document source that contains markers created by the collaboration features."),e.plugins.has("RestrictedEditingModeEditing")&&console.warn("You initialized the editor with the source editing feature and restricted editing feature. Please be advised that the source editing feature may not work, and be careful when editing document source that contains markers created by the restricted editing feature.")}_showSourceEditing(){const e=this.editor,t=e.editing.view,i=e.model;i.change((e=>{e.setSelection(null),e.removeSelectionAttribute(i.document.selection.getAttributeKeys())}));for(const[i,n]of t.domRoots){const r=m(e.data.get({rootName:i})),a=(0,o.createElement)(n.ownerDocument,"textarea",{rows:"1","aria-label":"Source code editing area"}),s=(0,o.createElement)(n.ownerDocument,"div",{class:"ck-source-editing-area","data-value":r},[a]);a.value=r,a.setSelectionRange(0,0),a.addEventListener("input",(()=>{s.dataset.value=a.value})),t.change((e=>{const n=t.document.getRoot(i);e.addClass("ck-hidden",n)})),this._replacedRoots.set(i,s),this._elementReplacer.replace(n,s),this._dataFromRoots.set(i,r)}this._focusSourceEditing()}_hideSourceEditing(){const e=this.editor.editing.view;this._updateEditorData(),e.change((t=>{for(const[i]of this._replacedRoots)t.removeClass("ck-hidden",e.document.getRoot(i))})),this._elementReplacer.restore(),this._replacedRoots.clear(),this._dataFromRoots.clear(),e.focus()}_updateEditorData(){const e=this.editor,t={};for(const[e,i]of this._replacedRoots){const n=this._dataFromRoots.get(e),o=i.dataset.value;n!==o&&(t[e]=o)}Object.keys(t).length&&e.data.set(t,{batchType:{isUndoable:!0}})}_focusSourceEditing(){const[e]=this._replacedRoots.values();e.querySelector("textarea").focus()}_disableCommands(){const e=this.editor;for(const t of e.commands.commands())t.forceDisabled(u)}_enableCommands(){const e=this.editor;for(const t of e.commands.commands())t.clearForceDisabled(u)}_handleReadOnlyMode(e){if(this.isSourceEditingMode)for(const[,t]of this._replacedRoots)t.querySelector("textarea").readOnly=e}_isAllowedToHandleSourceEditingMode(){const e=this.editor.ui.view.editable;return e&&!e._hasExternalElement}}function m(e){return function(e){return e.startsWith("<")}(e)?r(e):e}})(),(window.CKEditor5=window.CKEditor5||{}).sourceEditing=n})();