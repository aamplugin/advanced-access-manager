var ei=Object.defineProperty;var ti=(e,t,s)=>t in e?ei(e,t,{enumerable:!0,configurable:!0,writable:!0,value:s}):e[t]=s;var cn=(e,t,s)=>(ti(e,typeof t!="symbol"?t+"":t,s),s);(function(){const t=document.createElement("link").relList;if(t&&t.supports&&t.supports("modulepreload"))return;for(const o of document.querySelectorAll('link[rel="modulepreload"]'))n(o);new MutationObserver(o=>{for(const i of o)if(i.type==="childList")for(const r of i.addedNodes)r.tagName==="LINK"&&r.rel==="modulepreload"&&n(r)}).observe(document,{childList:!0,subtree:!0});function s(o){const i={};return o.integrity&&(i.integrity=o.integrity),o.referrerPolicy&&(i.referrerPolicy=o.referrerPolicy),o.crossOrigin==="use-credentials"?i.credentials="include":o.crossOrigin==="anonymous"?i.credentials="omit":i.credentials="same-origin",i}function n(o){if(o.ep)return;o.ep=!0;const i=s(o);fetch(o.href,i)}})();function Ys(e,t){const s=Object.create(null),n=e.split(",");for(let o=0;o<n.length;o++)s[n[o]]=!0;return t?o=>!!s[o.toLowerCase()]:o=>!!s[o]}const F={},lt=[],me=()=>{},si=()=>!1,qt=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&(e.charCodeAt(2)>122||e.charCodeAt(2)<97),Qs=e=>e.startsWith("onUpdate:"),re=Object.assign,Ps=(e,t)=>{const s=e.indexOf(t);s>-1&&e.splice(s,1)},ni=Object.prototype.hasOwnProperty,A=(e,t)=>ni.call(e,t),z=Array.isArray,ct=e=>ts(e)==="[object Map]",Fn=e=>ts(e)==="[object Set]",E=e=>typeof e=="function",X=e=>typeof e=="string",es=e=>typeof e=="symbol",H=e=>e!==null&&typeof e=="object",Bn=e=>(H(e)||E(e))&&E(e.then)&&E(e.catch),Hn=Object.prototype.toString,ts=e=>Hn.call(e),oi=e=>ts(e).slice(8,-1),Wn=e=>ts(e)==="[object Object]",$s=e=>X(e)&&e!=="NaN"&&e[0]!=="-"&&""+parseInt(e,10)===e,Ht=Ys(",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"),ss=e=>{const t=Object.create(null);return s=>t[s]||(t[s]=e(s))},ii=/-(\w)/g,Oe=ss(e=>e.replace(ii,(t,s)=>s?s.toUpperCase():"")),ri=/\B([A-Z])/g,Mt=ss(e=>e.replace(ri,"-$1").toLowerCase()),ns=ss(e=>e.charAt(0).toUpperCase()+e.slice(1)),Ns=ss(e=>e?`on${ns(e)}`:""),qe=(e,t)=>!Object.is(e,t),xs=(e,t)=>{for(let s=0;s<e.length;s++)e[s](t)},Kt=(e,t,s)=>{Object.defineProperty(e,t,{configurable:!0,enumerable:!1,value:s})},li=e=>{const t=parseFloat(e);return isNaN(t)?e:t};let un;const ws=()=>un||(un=typeof globalThis<"u"?globalThis:typeof self<"u"?self:typeof window<"u"?window:typeof global<"u"?global:{});function Rs(e){if(z(e)){const t={};for(let s=0;s<e.length;s++){const n=e[s],o=X(n)?fi(n):Rs(n);if(o)for(const i in o)t[i]=o[i]}return t}else if(X(e)||H(e))return e}const ci=/;(?![^(]*\))/g,ui=/:([^]+)/,ai=/\/\*[^]*?\*\//g;function fi(e){const t={};return e.replace(ai,"").split(ci).forEach(s=>{if(s){const n=s.split(ui);n.length>1&&(t[n[0].trim()]=n[1].trim())}}),t}function ze(e){let t="";if(X(e))t=e;else if(z(e))for(let s=0;s<e.length;s++){const n=ze(e[s]);n&&(t+=n+" ")}else if(H(e))for(const s in e)e[s]&&(t+=s+" ");return t.trim()}const di="itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly",gi=Ys(di);function Zn(e){return!!e||e===""}const at=e=>X(e)?e:e==null?"":z(e)||H(e)&&(e.toString===Hn||!E(e.toString))?JSON.stringify(e,Kn,2):String(e),Kn=(e,t)=>t&&t.__v_isRef?Kn(e,t.value):ct(t)?{[`Map(${t.size})`]:[...t.entries()].reduce((s,[n,o])=>(s[`${n} =>`]=o,s),{})}:Fn(t)?{[`Set(${t.size})`]:[...t.values()]}:H(t)&&!z(t)&&!Wn(t)?String(t):t;let ge;class Gn{constructor(t=!1){this.detached=t,this._active=!0,this.effects=[],this.cleanups=[],this.parent=ge,!t&&ge&&(this.index=(ge.scopes||(ge.scopes=[])).push(this)-1)}get active(){return this._active}run(t){if(this._active){const s=ge;try{return ge=this,t()}finally{ge=s}}}on(){ge=this}off(){ge=this.parent}stop(t){if(this._active){let s,n;for(s=0,n=this.effects.length;s<n;s++)this.effects[s].stop();for(s=0,n=this.cleanups.length;s<n;s++)this.cleanups[s]();if(this.scopes)for(s=0,n=this.scopes.length;s<n;s++)this.scopes[s].stop(!0);if(!this.detached&&this.parent&&!t){const o=this.parent.scopes.pop();o&&o!==this&&(this.parent.scopes[this.index]=o,o.index=this.index)}this.parent=void 0,this._active=!1}}}function Jn(e){return new Gn(e)}function Mi(e,t=ge){t&&t.active&&t.effects.push(e)}function Vn(){return ge}function pi(e){ge&&ge.cleanups.push(e)}const Fs=e=>{const t=new Set(e);return t.w=0,t.n=0,t},Xn=e=>(e.w&Re)>0,qn=e=>(e.n&Re)>0,hi=({deps:e})=>{if(e.length)for(let t=0;t<e.length;t++)e[t].w|=Re},Ni=e=>{const{deps:t}=e;if(t.length){let s=0;for(let n=0;n<t.length;n++){const o=t[n];Xn(o)&&!qn(o)?o.delete(e):t[s++]=o,o.w&=~Re,o.n&=~Re}t.length=s}},Gt=new WeakMap;let yt=0,Re=1;const Is=30;let Ne;const Ve=Symbol(""),js=Symbol("");class Bs{constructor(t,s=null,n){this.fn=t,this.scheduler=s,this.active=!0,this.deps=[],this.parent=void 0,Mi(this,n)}run(){if(!this.active)return this.fn();let t=Ne,s=Qe;for(;t;){if(t===this)return;t=t.parent}try{return this.parent=Ne,Ne=this,Qe=!0,Re=1<<++yt,yt<=Is?hi(this):an(this),this.fn()}finally{yt<=Is&&Ni(this),Re=1<<--yt,Ne=this.parent,Qe=s,this.parent=void 0,this.deferStop&&this.stop()}}stop(){Ne===this?this.deferStop=!0:this.active&&(an(this),this.onStop&&this.onStop(),this.active=!1)}}function an(e){const{deps:t}=e;if(t.length){for(let s=0;s<t.length;s++)t[s].delete(e);t.length=0}}let Qe=!0;const eo=[];function pt(){eo.push(Qe),Qe=!1}function ht(){const e=eo.pop();Qe=e===void 0?!0:e}function ae(e,t,s){if(Qe&&Ne){let n=Gt.get(e);n||Gt.set(e,n=new Map);let o=n.get(s);o||n.set(s,o=Fs()),to(o)}}function to(e,t){let s=!1;yt<=Is?qn(e)||(e.n|=Re,s=!Xn(e)):s=!e.has(Ne),s&&(e.add(Ne),Ne.deps.push(e))}function _e(e,t,s,n,o,i){const r=Gt.get(e);if(!r)return;let l=[];if(t==="clear")l=[...r.values()];else if(s==="length"&&z(e)){const u=Number(n);r.forEach((f,g)=>{(g==="length"||!es(g)&&g>=u)&&l.push(f)})}else switch(s!==void 0&&l.push(r.get(s)),t){case"add":z(e)?$s(s)&&l.push(r.get("length")):(l.push(r.get(Ve)),ct(e)&&l.push(r.get(js)));break;case"delete":z(e)||(l.push(r.get(Ve)),ct(e)&&l.push(r.get(js)));break;case"set":ct(e)&&l.push(r.get(Ve));break}if(l.length===1)l[0]&&zs(l[0]);else{const u=[];for(const f of l)f&&u.push(...f);zs(Fs(u))}}function zs(e,t){const s=z(e)?e:[...e];for(const n of s)n.computed&&fn(n);for(const n of s)n.computed||fn(n)}function fn(e,t){(e!==Ne||e.allowRecurse)&&(e.scheduler?e.scheduler():e.run())}function xi(e,t){var s;return(s=Gt.get(e))==null?void 0:s.get(t)}const mi=Ys("__proto__,__v_isRef,__isVue"),so=new Set(Object.getOwnPropertyNames(Symbol).filter(e=>e!=="arguments"&&e!=="caller").map(e=>Symbol[e]).filter(es)),dn=bi();function bi(){const e={};return["includes","indexOf","lastIndexOf"].forEach(t=>{e[t]=function(...s){const n=v(this);for(let i=0,r=this.length;i<r;i++)ae(n,"get",i+"");const o=n[t](...s);return o===-1||o===!1?n[t](...s.map(v)):o}}),["push","pop","shift","unshift","splice"].forEach(t=>{e[t]=function(...s){pt();const n=v(this)[t].apply(this,s);return ht(),n}}),e}function yi(e){const t=v(this);return ae(t,"has",e),t.hasOwnProperty(e)}class no{constructor(t=!1,s=!1){this._isReadonly=t,this._shallow=s}get(t,s,n){const o=this._isReadonly,i=this._shallow;if(s==="__v_isReactive")return!o;if(s==="__v_isReadonly")return o;if(s==="__v_isShallow")return i;if(s==="__v_raw"&&n===(o?i?vi:lo:i?ro:io).get(t))return t;const r=z(t);if(!o){if(r&&A(dn,s))return Reflect.get(dn,s,n);if(s==="hasOwnProperty")return yi}const l=Reflect.get(t,s,n);return(es(s)?so.has(s):mi(s))||(o||ae(t,"get",s),i)?l:G(l)?r&&$s(s)?l:l.value:H(l)?o?co(l):is(l):l}}class oo extends no{constructor(t=!1){super(!1,t)}set(t,s,n,o){let i=t[s];if(ft(i)&&G(i)&&!G(n))return!1;if(!this._shallow&&(!Jt(n)&&!ft(n)&&(i=v(i),n=v(n)),!z(t)&&G(i)&&!G(n)))return i.value=n,!0;const r=z(t)&&$s(s)?Number(s)<t.length:A(t,s),l=Reflect.set(t,s,n,o);return t===v(o)&&(r?qe(n,i)&&_e(t,"set",s,n):_e(t,"add",s,n)),l}deleteProperty(t,s){const n=A(t,s);t[s];const o=Reflect.deleteProperty(t,s);return o&&n&&_e(t,"delete",s,void 0),o}has(t,s){const n=Reflect.has(t,s);return(!es(s)||!so.has(s))&&ae(t,"has",s),n}ownKeys(t){return ae(t,"iterate",z(t)?"length":Ve),Reflect.ownKeys(t)}}class Di extends no{constructor(t=!1){super(!0,t)}set(t,s){return!0}deleteProperty(t,s){return!0}}const Ti=new oo,wi=new Di,Ii=new oo(!0),Hs=e=>e,os=e=>Reflect.getPrototypeOf(e);function Qt(e,t,s=!1,n=!1){e=e.__v_raw;const o=v(e),i=v(t);s||(qe(t,i)&&ae(o,"get",t),ae(o,"get",i));const{has:r}=os(o),l=n?Hs:s?Ks:Et;if(r.call(o,t))return l(e.get(t));if(r.call(o,i))return l(e.get(i));e!==o&&e.get(t)}function Pt(e,t=!1){const s=this.__v_raw,n=v(s),o=v(e);return t||(qe(e,o)&&ae(n,"has",e),ae(n,"has",o)),e===o?s.has(e):s.has(e)||s.has(o)}function $t(e,t=!1){return e=e.__v_raw,!t&&ae(v(e),"iterate",Ve),Reflect.get(e,"size",e)}function gn(e){e=v(e);const t=v(this);return os(t).has.call(t,e)||(t.add(e),_e(t,"add",e,e)),this}function Mn(e,t){t=v(t);const s=v(this),{has:n,get:o}=os(s);let i=n.call(s,e);i||(e=v(e),i=n.call(s,e));const r=o.call(s,e);return s.set(e,t),i?qe(t,r)&&_e(s,"set",e,t):_e(s,"add",e,t),this}function pn(e){const t=v(this),{has:s,get:n}=os(t);let o=s.call(t,e);o||(e=v(e),o=s.call(t,e)),n&&n.call(t,e);const i=t.delete(e);return o&&_e(t,"delete",e,void 0),i}function hn(){const e=v(this),t=e.size!==0,s=e.clear();return t&&_e(e,"clear",void 0,void 0),s}function Rt(e,t){return function(n,o){const i=this,r=i.__v_raw,l=v(r),u=t?Hs:e?Ks:Et;return!e&&ae(l,"iterate",Ve),r.forEach((f,g)=>n.call(o,u(f),u(g),i))}}function Ft(e,t,s){return function(...n){const o=this.__v_raw,i=v(o),r=ct(i),l=e==="entries"||e===Symbol.iterator&&r,u=e==="keys"&&r,f=o[e](...n),g=s?Hs:t?Ks:Et;return!t&&ae(i,"iterate",u?js:Ve),{next(){const{value:b,done:D}=f.next();return D?{value:b,done:D}:{value:l?[g(b[0]),g(b[1])]:g(b),done:D}},[Symbol.iterator](){return this}}}}function Se(e){return function(...t){return e==="delete"?!1:e==="clear"?void 0:this}}function ji(){const e={get(i){return Qt(this,i)},get size(){return $t(this)},has:Pt,add:gn,set:Mn,delete:pn,clear:hn,forEach:Rt(!1,!1)},t={get(i){return Qt(this,i,!1,!0)},get size(){return $t(this)},has:Pt,add:gn,set:Mn,delete:pn,clear:hn,forEach:Rt(!1,!0)},s={get(i){return Qt(this,i,!0)},get size(){return $t(this,!0)},has(i){return Pt.call(this,i,!0)},add:Se("add"),set:Se("set"),delete:Se("delete"),clear:Se("clear"),forEach:Rt(!0,!1)},n={get(i){return Qt(this,i,!0,!0)},get size(){return $t(this,!0)},has(i){return Pt.call(this,i,!0)},add:Se("add"),set:Se("set"),delete:Se("delete"),clear:Se("clear"),forEach:Rt(!0,!0)};return["keys","values","entries",Symbol.iterator].forEach(i=>{e[i]=Ft(i,!1,!1),s[i]=Ft(i,!0,!1),t[i]=Ft(i,!1,!0),n[i]=Ft(i,!0,!0)}),[e,s,t,n]}const[zi,Oi,Ei,Li]=ji();function Ws(e,t){const s=t?e?Li:Ei:e?Oi:zi;return(n,o,i)=>o==="__v_isReactive"?!e:o==="__v_isReadonly"?e:o==="__v_raw"?n:Reflect.get(A(s,o)&&o in n?s:n,o,i)}const _i={get:Ws(!1,!1)},Ci={get:Ws(!1,!0)},Ai={get:Ws(!0,!1)},io=new WeakMap,ro=new WeakMap,lo=new WeakMap,vi=new WeakMap;function Si(e){switch(e){case"Object":case"Array":return 1;case"Map":case"Set":case"WeakMap":case"WeakSet":return 2;default:return 0}}function ki(e){return e.__v_skip||!Object.isExtensible(e)?0:Si(oi(e))}function is(e){return ft(e)?e:Zs(e,!1,Ti,_i,io)}function Ui(e){return Zs(e,!1,Ii,Ci,ro)}function co(e){return Zs(e,!0,wi,Ai,lo)}function Zs(e,t,s,n,o){if(!H(e)||e.__v_raw&&!(t&&e.__v_isReactive))return e;const i=o.get(e);if(i)return i;const r=ki(e);if(r===0)return e;const l=new Proxy(e,r===2?n:s);return o.set(e,l),l}function Pe(e){return ft(e)?Pe(e.__v_raw):!!(e&&e.__v_isReactive)}function ft(e){return!!(e&&e.__v_isReadonly)}function Jt(e){return!!(e&&e.__v_isShallow)}function uo(e){return Pe(e)||ft(e)}function v(e){const t=e&&e.__v_raw;return t?v(t):e}function rs(e){return Kt(e,"__v_skip",!0),e}const Et=e=>H(e)?is(e):e,Ks=e=>H(e)?co(e):e;function ao(e){Qe&&Ne&&(e=v(e),to(e.dep||(e.dep=Fs())))}function fo(e,t){e=v(e);const s=e.dep;s&&zs(s)}function G(e){return!!(e&&e.__v_isRef===!0)}function go(e){return Yi(e,!1)}function Yi(e,t){return G(e)?e:new Qi(e,t)}class Qi{constructor(t,s){this.__v_isShallow=s,this.dep=void 0,this.__v_isRef=!0,this._rawValue=s?t:v(t),this._value=s?t:Et(t)}get value(){return ao(this),this._value}set value(t){const s=this.__v_isShallow||Jt(t)||ft(t);t=s?t:v(t),qe(t,this._rawValue)&&(this._rawValue=t,this._value=s?t:Et(t),fo(this))}}function Pi(e){return G(e)?e.value:e}const $i={get:(e,t,s)=>Pi(Reflect.get(e,t,s)),set:(e,t,s,n)=>{const o=e[t];return G(o)&&!G(s)?(o.value=s,!0):Reflect.set(e,t,s,n)}};function Mo(e){return Pe(e)?e:new Proxy(e,$i)}function Ri(e){const t=z(e)?new Array(e.length):{};for(const s in e)t[s]=Bi(e,s);return t}class Fi{constructor(t,s,n){this._object=t,this._key=s,this._defaultValue=n,this.__v_isRef=!0}get value(){const t=this._object[this._key];return t===void 0?this._defaultValue:t}set value(t){this._object[this._key]=t}get dep(){return xi(v(this._object),this._key)}}function Bi(e,t,s){const n=e[t];return G(n)?n:new Fi(e,t,s)}class Hi{constructor(t,s,n,o){this._setter=s,this.dep=void 0,this.__v_isRef=!0,this.__v_isReadonly=!1,this._dirty=!0,this.effect=new Bs(t,()=>{this._dirty||(this._dirty=!0,fo(this))}),this.effect.computed=this,this.effect.active=this._cacheable=!o,this.__v_isReadonly=n}get value(){const t=v(this);return ao(t),(t._dirty||!t._cacheable)&&(t._dirty=!1,t._value=t.effect.run()),t._value}set value(t){this._setter(t)}}function Wi(e,t,s=!1){let n,o;const i=E(e);return i?(n=e,o=me):(n=e.get,o=e.set),new Hi(n,o,i||!o,s)}function $e(e,t,s,n){let o;try{o=n?e(...n):e()}catch(i){ls(i,t,s)}return o}function be(e,t,s,n){if(E(e)){const i=$e(e,t,s,n);return i&&Bn(i)&&i.catch(r=>{ls(r,t,s)}),i}const o=[];for(let i=0;i<e.length;i++)o.push(be(e[i],t,s,n));return o}function ls(e,t,s,n=!0){const o=t?t.vnode:null;if(t){let i=t.parent;const r=t.proxy,l=s;for(;i;){const f=i.ec;if(f){for(let g=0;g<f.length;g++)if(f[g](e,r,l)===!1)return}i=i.parent}const u=t.appContext.config.errorHandler;if(u){$e(u,null,10,[e,r,l]);return}}Zi(e,s,o,n)}function Zi(e,t,s,n=!0){console.error(e)}let Lt=!1,Os=!1;const oe=[];let je=0;const ut=[];let Le=null,Ge=0;const po=Promise.resolve();let Gs=null;function ho(e){const t=Gs||po;return e?t.then(this?e.bind(this):e):t}function Ki(e){let t=je+1,s=oe.length;for(;t<s;){const n=t+s>>>1,o=oe[n],i=_t(o);i<e||i===e&&o.pre?t=n+1:s=n}return t}function Js(e){(!oe.length||!oe.includes(e,Lt&&e.allowRecurse?je+1:je))&&(e.id==null?oe.push(e):oe.splice(Ki(e.id),0,e),No())}function No(){!Lt&&!Os&&(Os=!0,Gs=po.then(mo))}function Gi(e){const t=oe.indexOf(e);t>je&&oe.splice(t,1)}function Ji(e){z(e)?ut.push(...e):(!Le||!Le.includes(e,e.allowRecurse?Ge+1:Ge))&&ut.push(e),No()}function Nn(e,t=Lt?je+1:0){for(;t<oe.length;t++){const s=oe[t];s&&s.pre&&(oe.splice(t,1),t--,s())}}function xo(e){if(ut.length){const t=[...new Set(ut)];if(ut.length=0,Le){Le.push(...t);return}for(Le=t,Le.sort((s,n)=>_t(s)-_t(n)),Ge=0;Ge<Le.length;Ge++)Le[Ge]();Le=null,Ge=0}}const _t=e=>e.id==null?1/0:e.id,Vi=(e,t)=>{const s=_t(e)-_t(t);if(s===0){if(e.pre&&!t.pre)return-1;if(t.pre&&!e.pre)return 1}return s};function mo(e){Os=!1,Lt=!0,oe.sort(Vi);const t=me;try{for(je=0;je<oe.length;je++){const s=oe[je];s&&s.active!==!1&&$e(s,null,14)}}finally{je=0,oe.length=0,xo(),Lt=!1,Gs=null,(oe.length||ut.length)&&mo()}}function Xi(e,t,...s){if(e.isUnmounted)return;const n=e.vnode.props||F;let o=s;const i=t.startsWith("update:"),r=i&&t.slice(7);if(r&&r in n){const g=`${r==="modelValue"?"model":r}Modifiers`,{number:b,trim:D}=n[g]||F;D&&(o=s.map(j=>X(j)?j.trim():j)),b&&(o=s.map(li))}let l,u=n[l=Ns(t)]||n[l=Ns(Oe(t))];!u&&i&&(u=n[l=Ns(Mt(t))]),u&&be(u,e,6,o);const f=n[l+"Once"];if(f){if(!e.emitted)e.emitted={};else if(e.emitted[l])return;e.emitted[l]=!0,be(f,e,6,o)}}function bo(e,t,s=!1){const n=t.emitsCache,o=n.get(e);if(o!==void 0)return o;const i=e.emits;let r={},l=!1;if(!E(e)){const u=f=>{const g=bo(f,t,!0);g&&(l=!0,re(r,g))};!s&&t.mixins.length&&t.mixins.forEach(u),e.extends&&u(e.extends),e.mixins&&e.mixins.forEach(u)}return!i&&!l?(H(e)&&n.set(e,null),null):(z(i)?i.forEach(u=>r[u]=null):re(r,i),H(e)&&n.set(e,r),r)}function cs(e,t){return!e||!qt(t)?!1:(t=t.slice(2).replace(/Once$/,""),A(e,t[0].toLowerCase()+t.slice(1))||A(e,Mt(t))||A(e,t))}let ie=null,yo=null;function Vt(e){const t=ie;return ie=e,yo=e&&e.type.__scopeId||null,t}function qi(e,t=ie,s){if(!t||e._n)return e;const n=(...o)=>{n._d&&On(-1);const i=Vt(t);let r;try{r=e(...o)}finally{Vt(i),n._d&&On(1)}return r};return n._n=!0,n._c=!0,n._d=!0,n}function ms(e){const{type:t,vnode:s,proxy:n,withProxy:o,props:i,propsOptions:[r],slots:l,attrs:u,emit:f,render:g,renderCache:b,data:D,setupState:j,ctx:P,inheritAttrs:_}=e;let Z,q;const V=Vt(e);try{if(s.shapeFlag&4){const L=o||n,K=L;Z=Ie(g.call(K,L,b,i,j,D,P)),q=u}else{const L=t;Z=Ie(L.length>1?L(i,{attrs:u,slots:l,emit:f}):L(i,null)),q=t.props?u:er(u)}}catch(L){zt.length=0,ls(L,e,1),Z=ce(Fe)}let ee=Z;if(q&&_!==!1){const L=Object.keys(q),{shapeFlag:K}=ee;L.length&&K&7&&(r&&L.some(Qs)&&(q=tr(q,r)),ee=dt(ee,q))}return s.dirs&&(ee=dt(ee),ee.dirs=ee.dirs?ee.dirs.concat(s.dirs):s.dirs),s.transition&&(ee.transition=s.transition),Z=ee,Vt(V),Z}const er=e=>{let t;for(const s in e)(s==="class"||s==="style"||qt(s))&&((t||(t={}))[s]=e[s]);return t},tr=(e,t)=>{const s={};for(const n in e)(!Qs(n)||!(n.slice(9)in t))&&(s[n]=e[n]);return s};function sr(e,t,s){const{props:n,children:o,component:i}=e,{props:r,children:l,patchFlag:u}=t,f=i.emitsOptions;if(t.dirs||t.transition)return!0;if(s&&u>=0){if(u&1024)return!0;if(u&16)return n?xn(n,r,f):!!r;if(u&8){const g=t.dynamicProps;for(let b=0;b<g.length;b++){const D=g[b];if(r[D]!==n[D]&&!cs(f,D))return!0}}}else return(o||l)&&(!l||!l.$stable)?!0:n===r?!1:n?r?xn(n,r,f):!0:!!r;return!1}function xn(e,t,s){const n=Object.keys(t);if(n.length!==Object.keys(e).length)return!0;for(let o=0;o<n.length;o++){const i=n[o];if(t[i]!==e[i]&&!cs(s,i))return!0}return!1}function nr({vnode:e,parent:t},s){for(;t&&t.subTree===e;)(e=t.vnode).el=s,t=t.parent}const Do="components";function Ye(e,t){return ir(Do,e,!0,t)||e}const or=Symbol.for("v-ndc");function ir(e,t,s=!0,n=!1){const o=ie||se;if(o){const i=o.type;if(e===Do){const l=qr(i,!1);if(l&&(l===t||l===Oe(t)||l===ns(Oe(t))))return i}const r=mn(o[e]||i[e],t)||mn(o.appContext[e],t);return!r&&n?i:r}}function mn(e,t){return e&&(e[t]||e[Oe(t)]||e[ns(Oe(t))])}const rr=e=>e.__isSuspense;function lr(e,t){t&&t.pendingBranch?z(e)?t.effects.push(...e):t.effects.push(e):Ji(e)}const Bt={};function Wt(e,t,s){return To(e,t,s)}function To(e,t,{immediate:s,deep:n,flush:o,onTrack:i,onTrigger:r}=F){var l;const u=Vn()===((l=se)==null?void 0:l.scope)?se:null;let f,g=!1,b=!1;if(G(e)?(f=()=>e.value,g=Jt(e)):Pe(e)?(f=()=>e,n=!0):z(e)?(b=!0,g=e.some(L=>Pe(L)||Jt(L)),f=()=>e.map(L=>{if(G(L))return L.value;if(Pe(L))return rt(L);if(E(L))return $e(L,u,2)})):E(e)?t?f=()=>$e(e,u,2):f=()=>{if(!(u&&u.isUnmounted))return D&&D(),be(e,u,3,[j])}:f=me,t&&n){const L=f;f=()=>rt(L())}let D,j=L=>{D=V.onStop=()=>{$e(L,u,4),D=V.onStop=void 0}},P;if(vt)if(j=me,t?s&&be(t,u,3,[f(),b?[]:void 0,j]):f(),o==="sync"){const L=sl();P=L.__watcherHandles||(L.__watcherHandles=[])}else return me;let _=b?new Array(e.length).fill(Bt):Bt;const Z=()=>{if(V.active)if(t){const L=V.run();(n||g||(b?L.some((K,Be)=>qe(K,_[Be])):qe(L,_)))&&(D&&D(),be(t,u,3,[L,_===Bt?void 0:b&&_[0]===Bt?[]:_,j]),_=L)}else V.run()};Z.allowRecurse=!!t;let q;o==="sync"?q=Z:o==="post"?q=()=>ue(Z,u&&u.suspense):(Z.pre=!0,u&&(Z.id=u.uid),q=()=>Js(Z));const V=new Bs(f,q);t?s?Z():_=V.run():o==="post"?ue(V.run.bind(V),u&&u.suspense):V.run();const ee=()=>{V.stop(),u&&u.scope&&Ps(u.scope.effects,V)};return P&&P.push(ee),ee}function cr(e,t,s){const n=this.proxy,o=X(e)?e.includes(".")?wo(n,e):()=>n[e]:e.bind(n,n);let i;E(t)?i=t:(i=t.handler,s=t);const r=se;gt(this);const l=To(o,i.bind(n),s);return r?gt(r):Xe(),l}function wo(e,t){const s=t.split(".");return()=>{let n=e;for(let o=0;o<s.length&&n;o++)n=n[s[o]];return n}}function rt(e,t){if(!H(e)||e.__v_skip||(t=t||new Set,t.has(e)))return e;if(t.add(e),G(e))rt(e.value,t);else if(z(e))for(let s=0;s<e.length;s++)rt(e[s],t);else if(Fn(e)||ct(e))e.forEach(s=>{rt(s,t)});else if(Wn(e))for(const s in e)rt(e[s],t);return e}function Ze(e,t,s,n){const o=e.dirs,i=t&&t.dirs;for(let r=0;r<o.length;r++){const l=o[r];i&&(l.oldValue=i[r].value);let u=l.dir[n];u&&(pt(),be(u,s,8,[e.el,l,e,t]),ht())}}const wt=e=>!!e.type.__asyncLoader,Io=e=>e.type.__isKeepAlive;function ur(e,t){jo(e,"a",t)}function ar(e,t){jo(e,"da",t)}function jo(e,t,s=se){const n=e.__wdc||(e.__wdc=()=>{let o=s;for(;o;){if(o.isDeactivated)return;o=o.parent}return e()});if(us(t,n,s),s){let o=s.parent;for(;o&&o.parent;)Io(o.parent.vnode)&&fr(n,t,s,o),o=o.parent}}function fr(e,t,s,n){const o=us(t,e,n,!0);zo(()=>{Ps(n[t],o)},s)}function us(e,t,s=se,n=!1){if(s){const o=s[e]||(s[e]=[]),i=t.__weh||(t.__weh=(...r)=>{if(s.isUnmounted)return;pt(),gt(s);const l=be(t,s,e,r);return Xe(),ht(),l});return n?o.unshift(i):o.push(i),i}}const Ce=e=>(t,s=se)=>(!vt||e==="sp")&&us(e,(...n)=>t(...n),s),dr=Ce("bm"),gr=Ce("m"),Mr=Ce("bu"),pr=Ce("u"),hr=Ce("bum"),zo=Ce("um"),Nr=Ce("sp"),xr=Ce("rtg"),mr=Ce("rtc");function br(e,t=se){us("ec",e,t)}function Oo(e,t,s,n){let o;const i=s&&s[n];if(z(e)||X(e)){o=new Array(e.length);for(let r=0,l=e.length;r<l;r++)o[r]=t(e[r],r,void 0,i&&i[r])}else if(typeof e=="number"){o=new Array(e);for(let r=0;r<e;r++)o[r]=t(r+1,r,void 0,i&&i[r])}else if(H(e))if(e[Symbol.iterator])o=Array.from(e,(r,l)=>t(r,l,void 0,i&&i[l]));else{const r=Object.keys(e);o=new Array(r.length);for(let l=0,u=r.length;l<u;l++){const f=r[l];o[l]=t(e[f],f,l,i&&i[l])}}else o=[];return s&&(s[n]=o),o}function yr(e,t,s={},n,o){if(ie.isCE||ie.parent&&wt(ie.parent)&&ie.parent.isCE)return t!=="default"&&(s.name=t),ce("slot",s,n&&n());let i=e[t];i&&i._c&&(i._d=!1),B();const r=i&&Eo(i(s)),l=et(Me,{key:s.key||r&&r.key||`_${t}`},r||(n?n():[]),r&&e._===1?64:-2);return!o&&l.scopeId&&(l.slotScopeIds=[l.scopeId+"-s"]),i&&i._c&&(i._d=!0),l}function Eo(e){return e.some(t=>Qo(t)?!(t.type===Fe||t.type===Me&&!Eo(t.children)):!0)?e:null}const Es=e=>e?Ro(e)?tn(e)||e.proxy:Es(e.parent):null,It=re(Object.create(null),{$:e=>e,$el:e=>e.vnode.el,$data:e=>e.data,$props:e=>e.props,$attrs:e=>e.attrs,$slots:e=>e.slots,$refs:e=>e.refs,$parent:e=>Es(e.parent),$root:e=>Es(e.root),$emit:e=>e.emit,$options:e=>Vs(e),$forceUpdate:e=>e.f||(e.f=()=>Js(e.update)),$nextTick:e=>e.n||(e.n=ho.bind(e.proxy)),$watch:e=>cr.bind(e)}),bs=(e,t)=>e!==F&&!e.__isScriptSetup&&A(e,t),Dr={get({_:e},t){const{ctx:s,setupState:n,data:o,props:i,accessCache:r,type:l,appContext:u}=e;let f;if(t[0]!=="$"){const j=r[t];if(j!==void 0)switch(j){case 1:return n[t];case 2:return o[t];case 4:return s[t];case 3:return i[t]}else{if(bs(n,t))return r[t]=1,n[t];if(o!==F&&A(o,t))return r[t]=2,o[t];if((f=e.propsOptions[0])&&A(f,t))return r[t]=3,i[t];if(s!==F&&A(s,t))return r[t]=4,s[t];Ls&&(r[t]=0)}}const g=It[t];let b,D;if(g)return t==="$attrs"&&ae(e,"get",t),g(e);if((b=l.__cssModules)&&(b=b[t]))return b;if(s!==F&&A(s,t))return r[t]=4,s[t];if(D=u.config.globalProperties,A(D,t))return D[t]},set({_:e},t,s){const{data:n,setupState:o,ctx:i}=e;return bs(o,t)?(o[t]=s,!0):n!==F&&A(n,t)?(n[t]=s,!0):A(e.props,t)||t[0]==="$"&&t.slice(1)in e?!1:(i[t]=s,!0)},has({_:{data:e,setupState:t,accessCache:s,ctx:n,appContext:o,propsOptions:i}},r){let l;return!!s[r]||e!==F&&A(e,r)||bs(t,r)||(l=i[0])&&A(l,r)||A(n,r)||A(It,r)||A(o.config.globalProperties,r)},defineProperty(e,t,s){return s.get!=null?e._.accessCache[t]=0:A(s,"value")&&this.set(e,t,s.value,null),Reflect.defineProperty(e,t,s)}};function bn(e){return z(e)?e.reduce((t,s)=>(t[s]=null,t),{}):e}let Ls=!0;function Tr(e){const t=Vs(e),s=e.proxy,n=e.ctx;Ls=!1,t.beforeCreate&&yn(t.beforeCreate,e,"bc");const{data:o,computed:i,methods:r,watch:l,provide:u,inject:f,created:g,beforeMount:b,mounted:D,beforeUpdate:j,updated:P,activated:_,deactivated:Z,beforeDestroy:q,beforeUnmount:V,destroyed:ee,unmounted:L,render:K,renderTracked:Be,renderTriggered:pe,errorCaptured:S,serverPrefetch:k,expose:te,inheritAttrs:fe,components:ye,directives:tt,filters:xt}=t;if(f&&wr(f,n,null),r)for(const W in r){const $=r[W];E($)&&(n[W]=$.bind(s))}if(o){const W=o.call(s,s);H(W)&&(e.data=is(W))}if(Ls=!0,i)for(const W in i){const $=i[W],He=E($)?$.bind(s,s):E($.get)?$.get.bind(s,s):me,Ut=!E($)&&E($.set)?$.set.bind(s):me,We=Bo({get:He,set:Ut});Object.defineProperty(n,W,{enumerable:!0,configurable:!0,get:()=>We.value,set:De=>We.value=De})}if(l)for(const W in l)Lo(l[W],n,s,W);if(u){const W=E(u)?u.call(s):u;Reflect.ownKeys(W).forEach($=>{Lr($,W[$])})}g&&yn(g,e,"c");function Y(W,$){z($)?$.forEach(He=>W(He.bind(s))):$&&W($.bind(s))}if(Y(dr,b),Y(gr,D),Y(Mr,j),Y(pr,P),Y(ur,_),Y(ar,Z),Y(br,S),Y(mr,Be),Y(xr,pe),Y(hr,V),Y(zo,L),Y(Nr,k),z(te))if(te.length){const W=e.exposed||(e.exposed={});te.forEach($=>{Object.defineProperty(W,$,{get:()=>s[$],set:He=>s[$]=He})})}else e.exposed||(e.exposed={});K&&e.render===me&&(e.render=K),fe!=null&&(e.inheritAttrs=fe),ye&&(e.components=ye),tt&&(e.directives=tt)}function wr(e,t,s=me){z(e)&&(e=_s(e));for(const n in e){const o=e[n];let i;H(o)?"default"in o?i=jt(o.from||n,o.default,!0):i=jt(o.from||n):i=jt(o),G(i)?Object.defineProperty(t,n,{enumerable:!0,configurable:!0,get:()=>i.value,set:r=>i.value=r}):t[n]=i}}function yn(e,t,s){be(z(e)?e.map(n=>n.bind(t.proxy)):e.bind(t.proxy),t,s)}function Lo(e,t,s,n){const o=n.includes(".")?wo(s,n):()=>s[n];if(X(e)){const i=t[e];E(i)&&Wt(o,i)}else if(E(e))Wt(o,e.bind(s));else if(H(e))if(z(e))e.forEach(i=>Lo(i,t,s,n));else{const i=E(e.handler)?e.handler.bind(s):t[e.handler];E(i)&&Wt(o,i,e)}}function Vs(e){const t=e.type,{mixins:s,extends:n}=t,{mixins:o,optionsCache:i,config:{optionMergeStrategies:r}}=e.appContext,l=i.get(t);let u;return l?u=l:!o.length&&!s&&!n?u=t:(u={},o.length&&o.forEach(f=>Xt(u,f,r,!0)),Xt(u,t,r)),H(t)&&i.set(t,u),u}function Xt(e,t,s,n=!1){const{mixins:o,extends:i}=t;i&&Xt(e,i,s,!0),o&&o.forEach(r=>Xt(e,r,s,!0));for(const r in t)if(!(n&&r==="expose")){const l=Ir[r]||s&&s[r];e[r]=l?l(e[r],t[r]):t[r]}return e}const Ir={data:Dn,props:Tn,emits:Tn,methods:Dt,computed:Dt,beforeCreate:le,created:le,beforeMount:le,mounted:le,beforeUpdate:le,updated:le,beforeDestroy:le,beforeUnmount:le,destroyed:le,unmounted:le,activated:le,deactivated:le,errorCaptured:le,serverPrefetch:le,components:Dt,directives:Dt,watch:zr,provide:Dn,inject:jr};function Dn(e,t){return t?e?function(){return re(E(e)?e.call(this,this):e,E(t)?t.call(this,this):t)}:t:e}function jr(e,t){return Dt(_s(e),_s(t))}function _s(e){if(z(e)){const t={};for(let s=0;s<e.length;s++)t[e[s]]=e[s];return t}return e}function le(e,t){return e?[...new Set([].concat(e,t))]:t}function Dt(e,t){return e?re(Object.create(null),e,t):t}function Tn(e,t){return e?z(e)&&z(t)?[...new Set([...e,...t])]:re(Object.create(null),bn(e),bn(t??{})):t}function zr(e,t){if(!e)return t;if(!t)return e;const s=re(Object.create(null),e);for(const n in t)s[n]=le(e[n],t[n]);return s}function _o(){return{app:null,config:{isNativeTag:si,performance:!1,globalProperties:{},optionMergeStrategies:{},errorHandler:void 0,warnHandler:void 0,compilerOptions:{}},mixins:[],components:{},directives:{},provides:Object.create(null),optionsCache:new WeakMap,propsCache:new WeakMap,emitsCache:new WeakMap}}let Or=0;function Er(e,t){return function(n,o=null){E(n)||(n=re({},n)),o!=null&&!H(o)&&(o=null);const i=_o(),r=new WeakSet;let l=!1;const u=i.app={_uid:Or++,_component:n,_props:o,_container:null,_context:i,_instance:null,version:nl,get config(){return i.config},set config(f){},use(f,...g){return r.has(f)||(f&&E(f.install)?(r.add(f),f.install(u,...g)):E(f)&&(r.add(f),f(u,...g))),u},mixin(f){return i.mixins.includes(f)||i.mixins.push(f),u},component(f,g){return g?(i.components[f]=g,u):i.components[f]},directive(f,g){return g?(i.directives[f]=g,u):i.directives[f]},mount(f,g,b){if(!l){const D=ce(n,o);return D.appContext=i,g&&t?t(D,f):e(D,f,b),l=!0,u._container=f,f.__vue_app__=u,tn(D.component)||D.component.proxy}},unmount(){l&&(e(null,u._container),delete u._container.__vue_app__)},provide(f,g){return i.provides[f]=g,u},runWithContext(f){Ct=u;try{return f()}finally{Ct=null}}};return u}}let Ct=null;function Lr(e,t){if(se){let s=se.provides;const n=se.parent&&se.parent.provides;n===s&&(s=se.provides=Object.create(n)),s[e]=t}}function jt(e,t,s=!1){const n=se||ie;if(n||Ct){const o=n?n.parent==null?n.vnode.appContext&&n.vnode.appContext.provides:n.parent.provides:Ct._context.provides;if(o&&e in o)return o[e];if(arguments.length>1)return s&&E(t)?t.call(n&&n.proxy):t}}function _r(){return!!(se||ie||Ct)}function Cr(e,t,s,n=!1){const o={},i={};Kt(i,fs,1),e.propsDefaults=Object.create(null),Co(e,t,o,i);for(const r in e.propsOptions[0])r in o||(o[r]=void 0);s?e.props=n?o:Ui(o):e.type.props?e.props=o:e.props=i,e.attrs=i}function Ar(e,t,s,n){const{props:o,attrs:i,vnode:{patchFlag:r}}=e,l=v(o),[u]=e.propsOptions;let f=!1;if((n||r>0)&&!(r&16)){if(r&8){const g=e.vnode.dynamicProps;for(let b=0;b<g.length;b++){let D=g[b];if(cs(e.emitsOptions,D))continue;const j=t[D];if(u)if(A(i,D))j!==i[D]&&(i[D]=j,f=!0);else{const P=Oe(D);o[P]=Cs(u,l,P,j,e,!1)}else j!==i[D]&&(i[D]=j,f=!0)}}}else{Co(e,t,o,i)&&(f=!0);let g;for(const b in l)(!t||!A(t,b)&&((g=Mt(b))===b||!A(t,g)))&&(u?s&&(s[b]!==void 0||s[g]!==void 0)&&(o[b]=Cs(u,l,b,void 0,e,!0)):delete o[b]);if(i!==l)for(const b in i)(!t||!A(t,b))&&(delete i[b],f=!0)}f&&_e(e,"set","$attrs")}function Co(e,t,s,n){const[o,i]=e.propsOptions;let r=!1,l;if(t)for(let u in t){if(Ht(u))continue;const f=t[u];let g;o&&A(o,g=Oe(u))?!i||!i.includes(g)?s[g]=f:(l||(l={}))[g]=f:cs(e.emitsOptions,u)||(!(u in n)||f!==n[u])&&(n[u]=f,r=!0)}if(i){const u=v(s),f=l||F;for(let g=0;g<i.length;g++){const b=i[g];s[b]=Cs(o,u,b,f[b],e,!A(f,b))}}return r}function Cs(e,t,s,n,o,i){const r=e[s];if(r!=null){const l=A(r,"default");if(l&&n===void 0){const u=r.default;if(r.type!==Function&&!r.skipFactory&&E(u)){const{propsDefaults:f}=o;s in f?n=f[s]:(gt(o),n=f[s]=u.call(null,t),Xe())}else n=u}r[0]&&(i&&!l?n=!1:r[1]&&(n===""||n===Mt(s))&&(n=!0))}return n}function Ao(e,t,s=!1){const n=t.propsCache,o=n.get(e);if(o)return o;const i=e.props,r={},l=[];let u=!1;if(!E(e)){const g=b=>{u=!0;const[D,j]=Ao(b,t,!0);re(r,D),j&&l.push(...j)};!s&&t.mixins.length&&t.mixins.forEach(g),e.extends&&g(e.extends),e.mixins&&e.mixins.forEach(g)}if(!i&&!u)return H(e)&&n.set(e,lt),lt;if(z(i))for(let g=0;g<i.length;g++){const b=Oe(i[g]);wn(b)&&(r[b]=F)}else if(i)for(const g in i){const b=Oe(g);if(wn(b)){const D=i[g],j=r[b]=z(D)||E(D)?{type:D}:re({},D);if(j){const P=zn(Boolean,j.type),_=zn(String,j.type);j[0]=P>-1,j[1]=_<0||P<_,(P>-1||A(j,"default"))&&l.push(b)}}}const f=[r,l];return H(e)&&n.set(e,f),f}function wn(e){return e[0]!=="$"}function In(e){const t=e&&e.toString().match(/^\s*(function|class) (\w+)/);return t?t[2]:e===null?"null":""}function jn(e,t){return In(e)===In(t)}function zn(e,t){return z(t)?t.findIndex(s=>jn(s,e)):E(t)&&jn(t,e)?0:-1}const vo=e=>e[0]==="_"||e==="$stable",Xs=e=>z(e)?e.map(Ie):[Ie(e)],vr=(e,t,s)=>{if(t._n)return t;const n=qi((...o)=>Xs(t(...o)),s);return n._c=!1,n},So=(e,t,s)=>{const n=e._ctx;for(const o in e){if(vo(o))continue;const i=e[o];if(E(i))t[o]=vr(o,i,n);else if(i!=null){const r=Xs(i);t[o]=()=>r}}},ko=(e,t)=>{const s=Xs(t);e.slots.default=()=>s},Sr=(e,t)=>{if(e.vnode.shapeFlag&32){const s=t._;s?(e.slots=v(t),Kt(t,"_",s)):So(t,e.slots={})}else e.slots={},t&&ko(e,t);Kt(e.slots,fs,1)},kr=(e,t,s)=>{const{vnode:n,slots:o}=e;let i=!0,r=F;if(n.shapeFlag&32){const l=t._;l?s&&l===1?i=!1:(re(o,t),!s&&l===1&&delete o._):(i=!t.$stable,So(t,o)),r=t}else t&&(ko(e,t),r={default:1});if(i)for(const l in o)!vo(l)&&r[l]==null&&delete o[l]};function As(e,t,s,n,o=!1){if(z(e)){e.forEach((D,j)=>As(D,t&&(z(t)?t[j]:t),s,n,o));return}if(wt(n)&&!o)return;const i=n.shapeFlag&4?tn(n.component)||n.component.proxy:n.el,r=o?null:i,{i:l,r:u}=e,f=t&&t.r,g=l.refs===F?l.refs={}:l.refs,b=l.setupState;if(f!=null&&f!==u&&(X(f)?(g[f]=null,A(b,f)&&(b[f]=null)):G(f)&&(f.value=null)),E(u))$e(u,l,12,[r,g]);else{const D=X(u),j=G(u);if(D||j){const P=()=>{if(e.f){const _=D?A(b,u)?b[u]:g[u]:u.value;o?z(_)&&Ps(_,i):z(_)?_.includes(i)||_.push(i):D?(g[u]=[i],A(b,u)&&(b[u]=g[u])):(u.value=[i],e.k&&(g[e.k]=u.value))}else D?(g[u]=r,A(b,u)&&(b[u]=r)):j&&(u.value=r,e.k&&(g[e.k]=r))};r?(P.id=-1,ue(P,s)):P()}}}const ue=lr;function Ur(e){return Yr(e)}function Yr(e,t){const s=ws();s.__VUE__=!0;const{insert:n,remove:o,patchProp:i,createElement:r,createText:l,createComment:u,setText:f,setElementText:g,parentNode:b,nextSibling:D,setScopeId:j=me,insertStaticContent:P}=e,_=(c,a,d,M=null,p=null,x=null,y=!1,N=null,m=!!a.dynamicChildren)=>{if(c===a)return;c&&!bt(c,a)&&(M=Yt(c),De(c,p,x,!0),c=null),a.patchFlag===-2&&(m=!1,a.dynamicChildren=null);const{type:h,ref:w,shapeFlag:T}=a;switch(h){case as:Z(c,a,d,M);break;case Fe:q(c,a,d,M);break;case ys:c==null&&V(a,d,M,y);break;case Me:ye(c,a,d,M,p,x,y,N,m);break;default:T&1?K(c,a,d,M,p,x,y,N,m):T&6?tt(c,a,d,M,p,x,y,N,m):(T&64||T&128)&&h.process(c,a,d,M,p,x,y,N,m,st)}w!=null&&p&&As(w,c&&c.ref,x,a||c,!a)},Z=(c,a,d,M)=>{if(c==null)n(a.el=l(a.children),d,M);else{const p=a.el=c.el;a.children!==c.children&&f(p,a.children)}},q=(c,a,d,M)=>{c==null?n(a.el=u(a.children||""),d,M):a.el=c.el},V=(c,a,d,M)=>{[c.el,c.anchor]=P(c.children,a,d,M,c.el,c.anchor)},ee=({el:c,anchor:a},d,M)=>{let p;for(;c&&c!==a;)p=D(c),n(c,d,M),c=p;n(a,d,M)},L=({el:c,anchor:a})=>{let d;for(;c&&c!==a;)d=D(c),o(c),c=d;o(a)},K=(c,a,d,M,p,x,y,N,m)=>{y=y||a.type==="svg",c==null?Be(a,d,M,p,x,y,N,m):k(c,a,p,x,y,N,m)},Be=(c,a,d,M,p,x,y,N)=>{let m,h;const{type:w,props:T,shapeFlag:I,transition:O,dirs:C}=c;if(m=c.el=r(c.type,x,T&&T.is,T),I&8?g(m,c.children):I&16&&S(c.children,m,null,M,p,x&&w!=="foreignObject",y,N),C&&Ze(c,null,M,"created"),pe(m,c,c.scopeId,y,M),T){for(const Q in T)Q!=="value"&&!Ht(Q)&&i(m,Q,null,T[Q],x,c.children,M,p,Ee);"value"in T&&i(m,"value",null,T.value),(h=T.onVnodeBeforeMount)&&we(h,M,c)}C&&Ze(c,null,M,"beforeMount");const R=Qr(p,O);R&&O.beforeEnter(m),n(m,a,d),((h=T&&T.onVnodeMounted)||R||C)&&ue(()=>{h&&we(h,M,c),R&&O.enter(m),C&&Ze(c,null,M,"mounted")},p)},pe=(c,a,d,M,p)=>{if(d&&j(c,d),M)for(let x=0;x<M.length;x++)j(c,M[x]);if(p){let x=p.subTree;if(a===x){const y=p.vnode;pe(c,y,y.scopeId,y.slotScopeIds,p.parent)}}},S=(c,a,d,M,p,x,y,N,m=0)=>{for(let h=m;h<c.length;h++){const w=c[h]=N?Ue(c[h]):Ie(c[h]);_(null,w,a,d,M,p,x,y,N)}},k=(c,a,d,M,p,x,y)=>{const N=a.el=c.el;let{patchFlag:m,dynamicChildren:h,dirs:w}=a;m|=c.patchFlag&16;const T=c.props||F,I=a.props||F;let O;d&&Ke(d,!1),(O=I.onVnodeBeforeUpdate)&&we(O,d,a,c),w&&Ze(a,c,d,"beforeUpdate"),d&&Ke(d,!0);const C=p&&a.type!=="foreignObject";if(h?te(c.dynamicChildren,h,N,d,M,C,x):y||$(c,a,N,null,d,M,C,x,!1),m>0){if(m&16)fe(N,a,T,I,d,M,p);else if(m&2&&T.class!==I.class&&i(N,"class",null,I.class,p),m&4&&i(N,"style",T.style,I.style,p),m&8){const R=a.dynamicProps;for(let Q=0;Q<R.length;Q++){const J=R[Q],he=T[J],nt=I[J];(nt!==he||J==="value")&&i(N,J,he,nt,p,c.children,d,M,Ee)}}m&1&&c.children!==a.children&&g(N,a.children)}else!y&&h==null&&fe(N,a,T,I,d,M,p);((O=I.onVnodeUpdated)||w)&&ue(()=>{O&&we(O,d,a,c),w&&Ze(a,c,d,"updated")},M)},te=(c,a,d,M,p,x,y)=>{for(let N=0;N<a.length;N++){const m=c[N],h=a[N],w=m.el&&(m.type===Me||!bt(m,h)||m.shapeFlag&70)?b(m.el):d;_(m,h,w,null,M,p,x,y,!0)}},fe=(c,a,d,M,p,x,y)=>{if(d!==M){if(d!==F)for(const N in d)!Ht(N)&&!(N in M)&&i(c,N,d[N],null,y,a.children,p,x,Ee);for(const N in M){if(Ht(N))continue;const m=M[N],h=d[N];m!==h&&N!=="value"&&i(c,N,h,m,y,a.children,p,x,Ee)}"value"in M&&i(c,"value",d.value,M.value)}},ye=(c,a,d,M,p,x,y,N,m)=>{const h=a.el=c?c.el:l(""),w=a.anchor=c?c.anchor:l("");let{patchFlag:T,dynamicChildren:I,slotScopeIds:O}=a;O&&(N=N?N.concat(O):O),c==null?(n(h,d,M),n(w,d,M),S(a.children,d,w,p,x,y,N,m)):T>0&&T&64&&I&&c.dynamicChildren?(te(c.dynamicChildren,I,d,p,x,y,N),(a.key!=null||p&&a===p.subTree)&&Uo(c,a,!0)):$(c,a,d,w,p,x,y,N,m)},tt=(c,a,d,M,p,x,y,N,m)=>{a.slotScopeIds=N,c==null?a.shapeFlag&512?p.ctx.activate(a,d,M,y,m):xt(a,d,M,p,x,y,m):ve(c,a,m)},xt=(c,a,d,M,p,x,y)=>{const N=c.component=Kr(c,M,p);if(Io(c)&&(N.ctx.renderer=st),Gr(N),N.asyncDep){if(p&&p.registerDep(N,Y),!c.el){const m=N.subTree=ce(Fe);q(null,m,a,d)}return}Y(N,c,a,d,p,x,y)},ve=(c,a,d)=>{const M=a.component=c.component;if(sr(c,a,d))if(M.asyncDep&&!M.asyncResolved){W(M,a,d);return}else M.next=a,Gi(M.update),M.update();else a.el=c.el,M.vnode=a},Y=(c,a,d,M,p,x,y)=>{const N=()=>{if(c.isMounted){let{next:w,bu:T,u:I,parent:O,vnode:C}=c,R=w,Q;Ke(c,!1),w?(w.el=C.el,W(c,w,y)):w=C,T&&xs(T),(Q=w.props&&w.props.onVnodeBeforeUpdate)&&we(Q,O,w,C),Ke(c,!0);const J=ms(c),he=c.subTree;c.subTree=J,_(he,J,b(he.el),Yt(he),c,p,x),w.el=J.el,R===null&&nr(c,J.el),I&&ue(I,p),(Q=w.props&&w.props.onVnodeUpdated)&&ue(()=>we(Q,O,w,C),p)}else{let w;const{el:T,props:I}=a,{bm:O,m:C,parent:R}=c,Q=wt(a);if(Ke(c,!1),O&&xs(O),!Q&&(w=I&&I.onVnodeBeforeMount)&&we(w,R,a),Ke(c,!0),T&&hs){const J=()=>{c.subTree=ms(c),hs(T,c.subTree,c,p,null)};Q?a.type.__asyncLoader().then(()=>!c.isUnmounted&&J()):J()}else{const J=c.subTree=ms(c);_(null,J,d,M,c,p,x),a.el=J.el}if(C&&ue(C,p),!Q&&(w=I&&I.onVnodeMounted)){const J=a;ue(()=>we(w,R,J),p)}(a.shapeFlag&256||R&&wt(R.vnode)&&R.vnode.shapeFlag&256)&&c.a&&ue(c.a,p),c.isMounted=!0,a=d=M=null}},m=c.effect=new Bs(N,()=>Js(h),c.scope),h=c.update=()=>m.run();h.id=c.uid,Ke(c,!0),h()},W=(c,a,d)=>{a.component=c;const M=c.vnode.props;c.vnode=a,c.next=null,Ar(c,a.props,M,d),kr(c,a.children,d),pt(),Nn(),ht()},$=(c,a,d,M,p,x,y,N,m=!1)=>{const h=c&&c.children,w=c?c.shapeFlag:0,T=a.children,{patchFlag:I,shapeFlag:O}=a;if(I>0){if(I&128){Ut(h,T,d,M,p,x,y,N,m);return}else if(I&256){He(h,T,d,M,p,x,y,N,m);return}}O&8?(w&16&&Ee(h,p,x),T!==h&&g(d,T)):w&16?O&16?Ut(h,T,d,M,p,x,y,N,m):Ee(h,p,x,!0):(w&8&&g(d,""),O&16&&S(T,d,M,p,x,y,N,m))},He=(c,a,d,M,p,x,y,N,m)=>{c=c||lt,a=a||lt;const h=c.length,w=a.length,T=Math.min(h,w);let I;for(I=0;I<T;I++){const O=a[I]=m?Ue(a[I]):Ie(a[I]);_(c[I],O,d,null,p,x,y,N,m)}h>w?Ee(c,p,x,!0,!1,T):S(a,d,M,p,x,y,N,m,T)},Ut=(c,a,d,M,p,x,y,N,m)=>{let h=0;const w=a.length;let T=c.length-1,I=w-1;for(;h<=T&&h<=I;){const O=c[h],C=a[h]=m?Ue(a[h]):Ie(a[h]);if(bt(O,C))_(O,C,d,null,p,x,y,N,m);else break;h++}for(;h<=T&&h<=I;){const O=c[T],C=a[I]=m?Ue(a[I]):Ie(a[I]);if(bt(O,C))_(O,C,d,null,p,x,y,N,m);else break;T--,I--}if(h>T){if(h<=I){const O=I+1,C=O<w?a[O].el:M;for(;h<=I;)_(null,a[h]=m?Ue(a[h]):Ie(a[h]),d,C,p,x,y,N,m),h++}}else if(h>I)for(;h<=T;)De(c[h],p,x,!0),h++;else{const O=h,C=h,R=new Map;for(h=C;h<=I;h++){const de=a[h]=m?Ue(a[h]):Ie(a[h]);de.key!=null&&R.set(de.key,h)}let Q,J=0;const he=I-C+1;let nt=!1,on=0;const mt=new Array(he);for(h=0;h<he;h++)mt[h]=0;for(h=O;h<=T;h++){const de=c[h];if(J>=he){De(de,p,x,!0);continue}let Te;if(de.key!=null)Te=R.get(de.key);else for(Q=C;Q<=I;Q++)if(mt[Q-C]===0&&bt(de,a[Q])){Te=Q;break}Te===void 0?De(de,p,x,!0):(mt[Te-C]=h+1,Te>=on?on=Te:nt=!0,_(de,a[Te],d,null,p,x,y,N,m),J++)}const rn=nt?Pr(mt):lt;for(Q=rn.length-1,h=he-1;h>=0;h--){const de=C+h,Te=a[de],ln=de+1<w?a[de+1].el:M;mt[h]===0?_(null,Te,d,ln,p,x,y,N,m):nt&&(Q<0||h!==rn[Q]?We(Te,d,ln,2):Q--)}}},We=(c,a,d,M,p=null)=>{const{el:x,type:y,transition:N,children:m,shapeFlag:h}=c;if(h&6){We(c.component.subTree,a,d,M);return}if(h&128){c.suspense.move(a,d,M);return}if(h&64){y.move(c,a,d,st);return}if(y===Me){n(x,a,d);for(let T=0;T<m.length;T++)We(m[T],a,d,M);n(c.anchor,a,d);return}if(y===ys){ee(c,a,d);return}if(M!==2&&h&1&&N)if(M===0)N.beforeEnter(x),n(x,a,d),ue(()=>N.enter(x),p);else{const{leave:T,delayLeave:I,afterLeave:O}=N,C=()=>n(x,a,d),R=()=>{T(x,()=>{C(),O&&O()})};I?I(x,C,R):R()}else n(x,a,d)},De=(c,a,d,M=!1,p=!1)=>{const{type:x,props:y,ref:N,children:m,dynamicChildren:h,shapeFlag:w,patchFlag:T,dirs:I}=c;if(N!=null&&As(N,null,d,c,!0),w&256){a.ctx.deactivate(c);return}const O=w&1&&I,C=!wt(c);let R;if(C&&(R=y&&y.onVnodeBeforeUnmount)&&we(R,a,c),w&6)qo(c.component,d,M);else{if(w&128){c.suspense.unmount(d,M);return}O&&Ze(c,null,a,"beforeUnmount"),w&64?c.type.remove(c,a,d,p,st,M):h&&(x!==Me||T>0&&T&64)?Ee(h,a,d,!1,!0):(x===Me&&T&384||!p&&w&16)&&Ee(m,a,d),M&&sn(c)}(C&&(R=y&&y.onVnodeUnmounted)||O)&&ue(()=>{R&&we(R,a,c),O&&Ze(c,null,a,"unmounted")},d)},sn=c=>{const{type:a,el:d,anchor:M,transition:p}=c;if(a===Me){Xo(d,M);return}if(a===ys){L(c);return}const x=()=>{o(d),p&&!p.persisted&&p.afterLeave&&p.afterLeave()};if(c.shapeFlag&1&&p&&!p.persisted){const{leave:y,delayLeave:N}=p,m=()=>y(d,x);N?N(c.el,x,m):m()}else x()},Xo=(c,a)=>{let d;for(;c!==a;)d=D(c),o(c),c=d;o(a)},qo=(c,a,d)=>{const{bum:M,scope:p,update:x,subTree:y,um:N}=c;M&&xs(M),p.stop(),x&&(x.active=!1,De(y,c,a,d)),N&&ue(N,a),ue(()=>{c.isUnmounted=!0},a),a&&a.pendingBranch&&!a.isUnmounted&&c.asyncDep&&!c.asyncResolved&&c.suspenseId===a.pendingId&&(a.deps--,a.deps===0&&a.resolve())},Ee=(c,a,d,M=!1,p=!1,x=0)=>{for(let y=x;y<c.length;y++)De(c[y],a,d,M,p)},Yt=c=>c.shapeFlag&6?Yt(c.component.subTree):c.shapeFlag&128?c.suspense.next():D(c.anchor||c.el),nn=(c,a,d)=>{c==null?a._vnode&&De(a._vnode,null,null,!0):_(a._vnode||null,c,a,null,null,null,d),Nn(),xo(),a._vnode=c},st={p:_,um:De,m:We,r:sn,mt:xt,mc:S,pc:$,pbc:te,n:Yt,o:e};let ps,hs;return t&&([ps,hs]=t(st)),{render:nn,hydrate:ps,createApp:Er(nn,ps)}}function Ke({effect:e,update:t},s){e.allowRecurse=t.allowRecurse=s}function Qr(e,t){return(!e||e&&!e.pendingBranch)&&t&&!t.persisted}function Uo(e,t,s=!1){const n=e.children,o=t.children;if(z(n)&&z(o))for(let i=0;i<n.length;i++){const r=n[i];let l=o[i];l.shapeFlag&1&&!l.dynamicChildren&&((l.patchFlag<=0||l.patchFlag===32)&&(l=o[i]=Ue(o[i]),l.el=r.el),s||Uo(r,l)),l.type===as&&(l.el=r.el)}}function Pr(e){const t=e.slice(),s=[0];let n,o,i,r,l;const u=e.length;for(n=0;n<u;n++){const f=e[n];if(f!==0){if(o=s[s.length-1],e[o]<f){t[n]=o,s.push(n);continue}for(i=0,r=s.length-1;i<r;)l=i+r>>1,e[s[l]]<f?i=l+1:r=l;f<e[s[i]]&&(i>0&&(t[n]=s[i-1]),s[i]=n)}}for(i=s.length,r=s[i-1];i-- >0;)s[i]=r,r=t[r];return s}const $r=e=>e.__isTeleport,Me=Symbol.for("v-fgt"),as=Symbol.for("v-txt"),Fe=Symbol.for("v-cmt"),ys=Symbol.for("v-stc"),zt=[];let xe=null;function B(e=!1){zt.push(xe=e?null:[])}function Rr(){zt.pop(),xe=zt[zt.length-1]||null}let At=1;function On(e){At+=e}function Yo(e){return e.dynamicChildren=At>0?xe||lt:null,Rr(),At>0&&xe&&xe.push(e),e}function ne(e,t,s,n,o,i){return Yo(U(e,t,s,n,o,i,!0))}function et(e,t,s,n,o){return Yo(ce(e,t,s,n,o,!0))}function Qo(e){return e?e.__v_isVNode===!0:!1}function bt(e,t){return e.type===t.type&&e.key===t.key}const fs="__vInternal",Po=({key:e})=>e??null,Zt=({ref:e,ref_key:t,ref_for:s})=>(typeof e=="number"&&(e=""+e),e!=null?X(e)||G(e)||E(e)?{i:ie,r:e,k:t,f:!!s}:e:null);function U(e,t=null,s=null,n=0,o=null,i=e===Me?0:1,r=!1,l=!1){const u={__v_isVNode:!0,__v_skip:!0,type:e,props:t,key:t&&Po(t),ref:t&&Zt(t),scopeId:yo,slotScopeIds:null,children:s,component:null,suspense:null,ssContent:null,ssFallback:null,dirs:null,transition:null,el:null,anchor:null,target:null,targetAnchor:null,staticCount:0,shapeFlag:i,patchFlag:n,dynamicProps:o,dynamicChildren:null,appContext:null,ctx:ie};return l?(qs(u,s),i&128&&e.normalize(u)):s&&(u.shapeFlag|=X(s)?8:16),At>0&&!r&&xe&&(u.patchFlag>0||i&6)&&u.patchFlag!==32&&xe.push(u),u}const ce=Fr;function Fr(e,t=null,s=null,n=0,o=null,i=!1){if((!e||e===or)&&(e=Fe),Qo(e)){const l=dt(e,t,!0);return s&&qs(l,s),At>0&&!i&&xe&&(l.shapeFlag&6?xe[xe.indexOf(e)]=l:xe.push(l)),l.patchFlag|=-2,l}if(el(e)&&(e=e.__vccOpts),t){t=Br(t);let{class:l,style:u}=t;l&&!X(l)&&(t.class=ze(l)),H(u)&&(uo(u)&&!z(u)&&(u=re({},u)),t.style=Rs(u))}const r=X(e)?1:rr(e)?128:$r(e)?64:H(e)?4:E(e)?2:0;return U(e,t,s,n,o,r,i,!0)}function Br(e){return e?uo(e)||fs in e?re({},e):e:null}function dt(e,t,s=!1){const{props:n,ref:o,patchFlag:i,children:r}=e,l=t?Hr(n||{},t):n;return{__v_isVNode:!0,__v_skip:!0,type:e.type,props:l,key:l&&Po(l),ref:t&&t.ref?s&&o?z(o)?o.concat(Zt(t)):[o,Zt(t)]:Zt(t):o,scopeId:e.scopeId,slotScopeIds:e.slotScopeIds,children:r,target:e.target,targetAnchor:e.targetAnchor,staticCount:e.staticCount,shapeFlag:e.shapeFlag,patchFlag:t&&e.type!==Me?i===-1?16:i|16:i,dynamicProps:e.dynamicProps,dynamicChildren:e.dynamicChildren,appContext:e.appContext,dirs:e.dirs,transition:e.transition,component:e.component,suspense:e.suspense,ssContent:e.ssContent&&dt(e.ssContent),ssFallback:e.ssFallback&&dt(e.ssFallback),el:e.el,anchor:e.anchor,ctx:e.ctx,ce:e.ce}}function $o(e=" ",t=0){return ce(as,null,e,t)}function St(e="",t=!1){return t?(B(),et(Fe,null,e)):ce(Fe,null,e)}function Ie(e){return e==null||typeof e=="boolean"?ce(Fe):z(e)?ce(Me,null,e.slice()):typeof e=="object"?Ue(e):ce(as,null,String(e))}function Ue(e){return e.el===null&&e.patchFlag!==-1||e.memo?e:dt(e)}function qs(e,t){let s=0;const{shapeFlag:n}=e;if(t==null)t=null;else if(z(t))s=16;else if(typeof t=="object")if(n&65){const o=t.default;o&&(o._c&&(o._d=!1),qs(e,o()),o._c&&(o._d=!0));return}else{s=32;const o=t._;!o&&!(fs in t)?t._ctx=ie:o===3&&ie&&(ie.slots._===1?t._=1:(t._=2,e.patchFlag|=1024))}else E(t)?(t={default:t,_ctx:ie},s=32):(t=String(t),n&64?(s=16,t=[$o(t)]):s=8);e.children=t,e.shapeFlag|=s}function Hr(...e){const t={};for(let s=0;s<e.length;s++){const n=e[s];for(const o in n)if(o==="class")t.class!==n.class&&(t.class=ze([t.class,n.class]));else if(o==="style")t.style=Rs([t.style,n.style]);else if(qt(o)){const i=t[o],r=n[o];r&&i!==r&&!(z(i)&&i.includes(r))&&(t[o]=i?[].concat(i,r):r)}else o!==""&&(t[o]=n[o])}return t}function we(e,t,s,n=null){be(e,t,7,[s,n])}const Wr=_o();let Zr=0;function Kr(e,t,s){const n=e.type,o=(t?t.appContext:e.appContext)||Wr,i={uid:Zr++,vnode:e,type:n,parent:t,appContext:o,root:null,next:null,subTree:null,effect:null,update:null,scope:new Gn(!0),render:null,proxy:null,exposed:null,exposeProxy:null,withProxy:null,provides:t?t.provides:Object.create(o.provides),accessCache:null,renderCache:[],components:null,directives:null,propsOptions:Ao(n,o),emitsOptions:bo(n,o),emit:null,emitted:null,propsDefaults:F,inheritAttrs:n.inheritAttrs,ctx:F,data:F,props:F,attrs:F,slots:F,refs:F,setupState:F,setupContext:null,attrsProxy:null,slotsProxy:null,suspense:s,suspenseId:s?s.pendingId:0,asyncDep:null,asyncResolved:!1,isMounted:!1,isUnmounted:!1,isDeactivated:!1,bc:null,c:null,bm:null,m:null,bu:null,u:null,um:null,bum:null,da:null,a:null,rtg:null,rtc:null,ec:null,sp:null};return i.ctx={_:i},i.root=t?t.root:i,i.emit=Xi.bind(null,i),e.ce&&e.ce(i),i}let se=null,en,ot,En="__VUE_INSTANCE_SETTERS__";(ot=ws()[En])||(ot=ws()[En]=[]),ot.push(e=>se=e),en=e=>{ot.length>1?ot.forEach(t=>t(e)):ot[0](e)};const gt=e=>{en(e),e.scope.on()},Xe=()=>{se&&se.scope.off(),en(null)};function Ro(e){return e.vnode.shapeFlag&4}let vt=!1;function Gr(e,t=!1){vt=t;const{props:s,children:n}=e.vnode,o=Ro(e);Cr(e,s,o,t),Sr(e,n);const i=o?Jr(e,t):void 0;return vt=!1,i}function Jr(e,t){const s=e.type;e.accessCache=Object.create(null),e.proxy=rs(new Proxy(e.ctx,Dr));const{setup:n}=s;if(n){const o=e.setupContext=n.length>1?Xr(e):null;gt(e),pt();const i=$e(n,e,0,[e.props,o]);if(ht(),Xe(),Bn(i)){if(i.then(Xe,Xe),t)return i.then(r=>{Ln(e,r,t)}).catch(r=>{ls(r,e,0)});e.asyncDep=i}else Ln(e,i,t)}else Fo(e,t)}function Ln(e,t,s){E(t)?e.type.__ssrInlineRender?e.ssrRender=t:e.render=t:H(t)&&(e.setupState=Mo(t)),Fo(e,s)}let _n;function Fo(e,t,s){const n=e.type;if(!e.render){if(!t&&_n&&!n.render){const o=n.template||Vs(e).template;if(o){const{isCustomElement:i,compilerOptions:r}=e.appContext.config,{delimiters:l,compilerOptions:u}=n,f=re(re({isCustomElement:i,delimiters:l},r),u);n.render=_n(o,f)}}e.render=n.render||me}{gt(e),pt();try{Tr(e)}finally{ht(),Xe()}}}function Vr(e){return e.attrsProxy||(e.attrsProxy=new Proxy(e.attrs,{get(t,s){return ae(e,"get","$attrs"),t[s]}}))}function Xr(e){const t=s=>{e.exposed=s||{}};return{get attrs(){return Vr(e)},slots:e.slots,emit:e.emit,expose:t}}function tn(e){if(e.exposed)return e.exposeProxy||(e.exposeProxy=new Proxy(Mo(rs(e.exposed)),{get(t,s){if(s in t)return t[s];if(s in It)return It[s](e)},has(t,s){return s in t||s in It}}))}function qr(e,t=!0){return E(e)?e.displayName||e.name:e.name||t&&e.__name}function el(e){return E(e)&&"__vccOpts"in e}const Bo=(e,t)=>Wi(e,t,vt),tl=Symbol.for("v-scx"),sl=()=>jt(tl),nl="3.3.10",ol="http://www.w3.org/2000/svg",Je=typeof document<"u"?document:null,Cn=Je&&Je.createElement("template"),il={insert:(e,t,s)=>{t.insertBefore(e,s||null)},remove:e=>{const t=e.parentNode;t&&t.removeChild(e)},createElement:(e,t,s,n)=>{const o=t?Je.createElementNS(ol,e):Je.createElement(e,s?{is:s}:void 0);return e==="select"&&n&&n.multiple!=null&&o.setAttribute("multiple",n.multiple),o},createText:e=>Je.createTextNode(e),createComment:e=>Je.createComment(e),setText:(e,t)=>{e.nodeValue=t},setElementText:(e,t)=>{e.textContent=t},parentNode:e=>e.parentNode,nextSibling:e=>e.nextSibling,querySelector:e=>Je.querySelector(e),setScopeId(e,t){e.setAttribute(t,"")},insertStaticContent(e,t,s,n,o,i){const r=s?s.previousSibling:t.lastChild;if(o&&(o===i||o.nextSibling))for(;t.insertBefore(o.cloneNode(!0),s),!(o===i||!(o=o.nextSibling)););else{Cn.innerHTML=n?`<svg>${e}</svg>`:e;const l=Cn.content;if(n){const u=l.firstChild;for(;u.firstChild;)l.appendChild(u.firstChild);l.removeChild(u)}t.insertBefore(l,s)}return[r?r.nextSibling:t.firstChild,s?s.previousSibling:t.lastChild]}},rl=Symbol("_vtc");function ll(e,t,s){const n=e[rl];n&&(t=(t?[t,...n]:[...n]).join(" ")),t==null?e.removeAttribute("class"):s?e.setAttribute("class",t):e.className=t}const cl=Symbol("_vod");function ul(e,t,s){const n=e.style,o=X(s);if(s&&!o){if(t&&!X(t))for(const i in t)s[i]==null&&vs(n,i,"");for(const i in s)vs(n,i,s[i])}else{const i=n.display;o?t!==s&&(n.cssText=s):t&&e.removeAttribute("style"),cl in e&&(n.display=i)}}const An=/\s*!important$/;function vs(e,t,s){if(z(s))s.forEach(n=>vs(e,t,n));else if(s==null&&(s=""),t.startsWith("--"))e.setProperty(t,s);else{const n=al(e,t);An.test(s)?e.setProperty(Mt(n),s.replace(An,""),"important"):e[n]=s}}const vn=["Webkit","Moz","ms"],Ds={};function al(e,t){const s=Ds[t];if(s)return s;let n=Oe(t);if(n!=="filter"&&n in e)return Ds[t]=n;n=ns(n);for(let o=0;o<vn.length;o++){const i=vn[o]+n;if(i in e)return Ds[t]=i}return t}const Sn="http://www.w3.org/1999/xlink";function fl(e,t,s,n,o){if(n&&t.startsWith("xlink:"))s==null?e.removeAttributeNS(Sn,t.slice(6,t.length)):e.setAttributeNS(Sn,t,s);else{const i=gi(t);s==null||i&&!Zn(s)?e.removeAttribute(t):e.setAttribute(t,i?"":s)}}function dl(e,t,s,n,o,i,r){if(t==="innerHTML"||t==="textContent"){n&&r(n,o,i),e[t]=s??"";return}const l=e.tagName;if(t==="value"&&l!=="PROGRESS"&&!l.includes("-")){e._value=s;const f=l==="OPTION"?e.getAttribute("value"):e.value,g=s??"";f!==g&&(e.value=g),s==null&&e.removeAttribute(t);return}let u=!1;if(s===""||s==null){const f=typeof e[t];f==="boolean"?s=Zn(s):s==null&&f==="string"?(s="",u=!0):f==="number"&&(s=0,u=!0)}try{e[t]=s}catch{}u&&e.removeAttribute(t)}function gl(e,t,s,n){e.addEventListener(t,s,n)}function Ml(e,t,s,n){e.removeEventListener(t,s,n)}const kn=Symbol("_vei");function pl(e,t,s,n,o=null){const i=e[kn]||(e[kn]={}),r=i[t];if(n&&r)r.value=n;else{const[l,u]=hl(t);if(n){const f=i[t]=ml(n,o);gl(e,l,f,u)}else r&&(Ml(e,l,r,u),i[t]=void 0)}}const Un=/(?:Once|Passive|Capture)$/;function hl(e){let t;if(Un.test(e)){t={};let n;for(;n=e.match(Un);)e=e.slice(0,e.length-n[0].length),t[n[0].toLowerCase()]=!0}return[e[2]===":"?e.slice(3):Mt(e.slice(2)),t]}let Ts=0;const Nl=Promise.resolve(),xl=()=>Ts||(Nl.then(()=>Ts=0),Ts=Date.now());function ml(e,t){const s=n=>{if(!n._vts)n._vts=Date.now();else if(n._vts<=s.attached)return;be(bl(n,s.value),t,5,[n])};return s.value=e,s.attached=xl(),s}function bl(e,t){if(z(t)){const s=e.stopImmediatePropagation;return e.stopImmediatePropagation=()=>{s.call(e),e._stopped=!0},t.map(n=>o=>!o._stopped&&n&&n(o))}else return t}const Yn=e=>e.charCodeAt(0)===111&&e.charCodeAt(1)===110&&e.charCodeAt(2)>96&&e.charCodeAt(2)<123,yl=(e,t,s,n,o=!1,i,r,l,u)=>{t==="class"?ll(e,n,o):t==="style"?ul(e,s,n):qt(t)?Qs(t)||pl(e,t,s,n,r):(t[0]==="."?(t=t.slice(1),!0):t[0]==="^"?(t=t.slice(1),!1):Dl(e,t,n,o))?dl(e,t,n,i,r,l,u):(t==="true-value"?e._trueValue=n:t==="false-value"&&(e._falseValue=n),fl(e,t,n,o))};function Dl(e,t,s,n){if(n)return!!(t==="innerHTML"||t==="textContent"||t in e&&Yn(t)&&E(s));if(t==="spellcheck"||t==="draggable"||t==="translate"||t==="form"||t==="list"&&e.tagName==="INPUT"||t==="type"&&e.tagName==="TEXTAREA")return!1;if(t==="width"||t==="height"){const o=e.tagName;return!(o==="IMG"||o==="VIDEO"||o==="CANVAS"||o==="SOURCE")}return Yn(t)&&X(s)?!1:t in e}const Tl=["ctrl","shift","alt","meta"],wl={stop:e=>e.stopPropagation(),prevent:e=>e.preventDefault(),self:e=>e.target!==e.currentTarget,ctrl:e=>!e.ctrlKey,shift:e=>!e.shiftKey,alt:e=>!e.altKey,meta:e=>!e.metaKey,left:e=>"button"in e&&e.button!==0,middle:e=>"button"in e&&e.button!==1,right:e=>"button"in e&&e.button!==2,exact:(e,t)=>Tl.some(s=>e[`${s}Key`]&&!t.includes(s))},Ss=(e,t)=>e._withMods||(e._withMods=(s,...n)=>{for(let o=0;o<t.length;o++){const i=wl[t[o]];if(i&&i(s,t))return}return e(s,...n)}),Il=re({patchProp:yl},il);let Qn;function jl(){return Qn||(Qn=Ur(Il))}const zl=(...e)=>{const t=jl().createApp(...e),{mount:s}=t;return t.mount=n=>{const o=Ol(n);if(!o)return;const i=t._component;!E(i)&&!i.render&&!i.template&&(i.template=o.innerHTML),o.innerHTML="";const r=s(o,!1,o instanceof SVGElement);return o instanceof Element&&(o.removeAttribute("v-cloak"),o.setAttribute("data-v-app","")),r},t};function Ol(e){return X(e)?document.querySelector(e):e}const Ae=(e,t)=>{const s=e.__vccOpts||e;for(const[n,o]of t)s[n]=o;return s},El={},Ll={class:"cb-header"},_l=["src","alt"],Cl={class:"cb-header--title"},Al=U("sup",null,"BETA",-1),vl=["src","alt"];function Sl(e,t){return B(),ne("div",Ll,[U("img",{class:"cb-header--img",src:e.$theme.header.icons.logoImg,alt:e.$i18n.header.icons.logoAlt},null,8,_l),U("div",Cl,[U("small",null,at(e.$i18n.header.prompt),1),$o(" "+at(e.$i18n.name),1),Al]),U("div",{class:"cb-header--close-button",onClick:t[0]||(t[0]=s=>e.$emit("close"))},[U("img",{src:e.$theme.header.icons.closeImg,alt:e.$i18n.header.icons.closeAlt},null,8,vl)])])}const kl=Ae(El,[["render",Sl]]),Ul={props:{message:{type:Object,required:!0}},computed:{hasSources(){return Array.isArray(this.message.sources)&&this.message.sources.length>0}}},Yl={class:"cb-message--text"},Ql=["innerHTML"],Pl={key:0},$l={class:"cb-additional-sources-title"},Rl={class:"cb-additional-sources-list"},Fl=["href","alt"];function Bl(e,t,s,n,o,i){return B(),ne("div",Yl,[yr(e.$slots,"default",{message:s.message},()=>[U("p",{class:"cb-message--text-content",innerHTML:s.message.content},null,8,Ql)]),i.hasSources?(B(),ne("div",Pl,[U("span",$l,at(e.$i18n.message.sourcesTitle),1),U("ul",Rl,[(B(!0),ne(Me,null,Oo(s.message.sources,r=>(B(),ne("li",{key:r},[U("a",{href:r.link,alt:r.title,target:"_blank"},at(r.title),9,Fl)]))),128))])])):St("",!0)])}const Hl=Ae(Ul,[["render",Bl]]),Wl={},Zl={class:"cb-message--typing"},Kl=U("span",null,null,-1),Gl=U("span",null,null,-1),Jl=U("span",null,null,-1),Vl=[Kl,Gl,Jl];function Xl(e,t){return B(),ne("div",Zl,Vl)}const ql=Ae(Wl,[["render",Xl]]),ec={components:{TextMessage:Hl,TypingMessage:ql},props:{message:{type:Object,required:!0}},methods:{captureFeedback(e,t){t.feedback=e?"y":"n";const s={id:this.$conversation.id,message:t};this.$api.update(s)}}},tc=["id"],sc={key:0,class:"cb-message-feedback"},nc=U("span",null,"Was it helpful?",-1),oc=["src","alt"],ic=["src","alt"];function rc(e,t,s,n,o,i){const r=Ye("TypingMessage"),l=Ye("TextMessage");return B(),ne("div",{id:s.message.id,class:ze({"cb-message":!0,"cb-has-feedback":e.$settings.captureFeedback&&s.message.capture_feedback})},[U("div",{class:ze(["cb-message--content",{sent:s.message.role==="user",received:s.message.role!=="user"&&s.message.type!=="system",system:s.message.type==="system"}])},[s.message.type==="typing"?(B(),et(r,{key:0})):(B(),et(l,{key:1,message:s.message},null,8,["message"]))],2),e.$settings.captureFeedback&&s.message.capture_feedback?(B(),ne("div",sc,[nc,U("img",{src:e.$theme.message.icons.thumbUpImg,alt:e.$i18n.message.icons.thumbUpAlt,class:ze(["cb-feedback-action thumb-up",{selected:s.message.feedback==="y"}]),onClick:t[0]||(t[0]=u=>i.captureFeedback(!0,s.message))},null,10,oc),U("img",{src:e.$theme.message.icons.thumbDownImg,alt:e.$i18n.message.icons.thumbDownAlt,class:ze(["cb-feedback-action thumb-down",{selected:s.message.feedback==="n"}]),onClick:t[1]||(t[1]=u=>i.captureFeedback(!1,s.message))},null,10,ic)])):St("",!0)],10,tc)}const lc=Ae(ec,[["render",rc]]),cc={components:{Message:lc},data(){return{windowResizer:null}},mounted(){this.initWindowResizer(),this.$nextTick(this.scrollToBottom())},beforeUnmount(){this.windowResizer.unobserve(this.$refs.messageList)},updated(){this.$nextTick(this.scrollToBottom())},methods:{scrollToBottom(){this.$refs.messageList.scrollTop=this.$refs.messageList.scrollHeight},initWindowResizer(){this.windowResizer=new ResizeObserver(()=>{this.scrollToBottom()}),this.windowResizer.observe(this.$refs.messageList)}}},uc={ref:"messageList",class:"cb-message-list"};function ac(e,t,s,n,o,i){const r=Ye("Message");return B(),ne("div",uc,[(B(!0),ne(Me,null,Oo(e.$conversation.messages,l=>(B(),et(r,{key:l,message:l},null,8,["message"]))),128)),e.$store.typing?(B(),et(r,{key:0,message:{type:"typing"}})):St("",!0)],512)}const fc=Ae(cc,[["render",ac]]),dc={props:{onSubmit:{type:Function,required:!0}},data(){return{inputActive:!1}},methods:{setInputActive(e){this.inputActive=e},handleKey(e){e.keyCode===13&&!e.shiftKey&&(this._submitText(e),e.preventDefault())},_submitText(){const e=this.$refs.userInput.textContent;e&&e.length>0&&(this.$emit("submit",e),this.$refs.userInput.innerHTML="")},restart(){this.$conversation.reset()}}},gc=["placeholder"],Mc={class:"cb-user-input--buttons"},pc={class:"cb-user-input--button"},hc={class:"tooltip"},Nc=["src","alt"],xc={class:"tooltiptext"},mc={class:"cb-user-input--button"},bc={class:"tooltip"},yc=["src","alt"],Dc={class:"tooltiptext"};function Tc(e,t,s,n,o,i){return B(),ne("div",{class:ze(["cb-user-input",{active:o.inputActive}])},[U("div",{ref:"userInput",role:"button",tabIndex:"0",contentEditable:"true",placeholder:e.$i18n.input.placeholder,class:"cb-user-input--text",onFocus:t[0]||(t[0]=r=>i.setInputActive(!0)),onBlur:t[1]||(t[1]=r=>i.setInputActive(!1)),onKeydown:t[2]||(t[2]=(...r)=>i.handleKey&&i.handleKey(...r))},null,40,gc),U("div",Mc,[U("div",pc,[U("div",hc,[U("img",{src:e.$theme.input.icons.sendImg,alt:e.$i18n.input.icons.sendAlt,onClick:t[3]||(t[3]=Ss((...r)=>i._submitText&&i._submitText(...r),["prevent"]))},null,8,Nc),U("span",xc,at(e.$i18n.input.tooltip.sendBtn),1)])]),U("div",mc,[U("div",bc,[U("img",{src:e.$theme.input.icons.restartImg,alt:e.$theme.input.icons.restartAlt,onClick:t[4]||(t[4]=Ss((...r)=>i.restart&&i.restart(...r),["prevent"]))},null,8,yc),U("span",Dc,at(e.$i18n.input.tooltip.restartBtn),1)])])])],2)}const wc=Ae(dc,[["render",Tc]]),Ic={props:{show:Boolean}},jc={key:0,class:"cb-processing"};function zc(e,t,s,n,o,i){return s.show?(B(),ne("div",jc)):St("",!0)}const Oc=Ae(Ic,[["render",zc]]),Ec={components:{Header:kl,MessageList:fc,UserInput:wc,ProcessingLine:Oc},props:{isOpen:{type:Boolean,default:()=>!1}},data:function(){return{postInterval:null,thresholds:0,processing:!1}},methods:{submitUserMessage(e){const t=this;this.$conversation.addMessage(e),this.isOpen||(this.$store.hasNewMessage=!0),this.thresholds=0,this.postInterval=setInterval(function(){t.thresholds++;const s=t.$settings.thresholds[t.thresholds];s&&(s.action==="showTyping"?t.$store.typing=!0:s.action==="hideTyping"?t.$store.typing=!1:s.action==="halt"?(t.postInterval=clearInterval(t.postInterval),t.$conversation.addMessage(t.$i18n._(s.message),"assistant")):s.action==="showWaiting"&&t.$conversation.addMessage(t.$i18n._(s.message),"assistant"))},1e3),this.processing=!0,this.$api.post({id:this.$conversation.id,message:e},s=>{t.postInterval=clearInterval(t.postInterval),t.$store.typing=!1,t.processing=!1,s!==null?t.$conversation.addMessage({id:s.id,role:"assistant",content:s.answer,sources:s.sources||null,capture_feedback:s.type==="answer"}):t.$conversation.addMessage(t.$i18n.unexpectedFailure,"assistant")})}}};function Lc(e,t,s,n,o,i){const r=Ye("Header"),l=Ye("MessageList"),u=Ye("ProcessingLine"),f=Ye("UserInput");return B(),ne("div",{class:ze(["cb-chat-window",{opened:s.isOpen,closed:!s.isOpen,[e.$theme.window.size]:!0}])},[ce(r,{onClose:t[0]||(t[0]=g=>e.$emit("close"))}),ce(l),ce(u,{show:e.processing},null,8,["show"]),ce(f,{onSubmit:t[1]||(t[1]=g=>i.submitUserMessage(g))})],2)}const _c=Ae(Ec,[["render",Lc]]),Cc={components:{ChatWindow:_c},data(){return{isOpen:!1}}},Ac={key:0,class:"cb-notification"},vc=["src","alt"],Sc=["src","alt"];function kc(e,t,s,n,o,i){const r=Ye("ChatWindow");return B(),ne("div",null,[U("div",{class:ze(["cb-launcher",{opened:o.isOpen,hidden:e.$settings.hideLauncher}]),onClick:t[0]||(t[0]=Ss(l=>o.isOpen=!o.isOpen,["prevent"]))},[e.$store.hasNewMessage&&!o.isOpen?(B(),ne("div",Ac)):St("",!0),o.isOpen?(B(),ne("img",{key:1,class:"cb-closed-icon",src:e.$theme.launcher.icons.closeImg,alt:e.$i18n.launcher.icons.closeAlt},null,8,vc)):(B(),ne("img",{key:2,class:"cb-open-icon",src:e.$theme.launcher.icons.openImg,alt:e.$i18n.launcher.icons.openAlt},null,8,Sc))],2),ce(r,{"is-open":o.isOpen,onClose:t[1]||(t[1]=l=>o.isOpen=!1)},null,8,["is-open"])])}const Uc=Ae(Cc,[["render",kc]]),Yc={__name:"App",setup(e){return(t,s)=>(B(),et(Uc))}};function Tt(){const e=[];for(let i=0;i<256;i++)e[i]=(i<16?"0":"")+i.toString(16);const t=Math.random()*4294967295|0,s=Math.random()*4294967295|0,n=Math.random()*4294967295|0,o=Math.random()*4294967295|0;return e[t&255]+e[t>>8&255]+e[t>>16&255]+e[t>>24&255]+"-"+e[s&255]+e[s>>8&255]+"-"+e[s>>16&15|64]+e[s>>24&255]+"-"+e[n&63|128]+e[n>>8&255]+"-"+e[n>>16&255]+e[n>>24&255]+e[o&255]+e[o>>8&255]+e[o>>16&255]+e[o>>24&255]}function kt(e,t){let s={};if(typeof e=="object"&&typeof t=="object"){const n=Object.keys(e);for(let o of n)t[o]===void 0?s[o]=e[o]:typeof e[o]=="object"&&typeof t[o]=="object"?s[o]=kt(e[o],t[o]):typeof e[o]=="object"&&typeof t[o]!="object"?s[o]=e[o]:s[o]=t[o]}else s=typeof e=="object"?e:t;return s}function ds(e,t={}){let s=t;return typeof TLDR_Chatbot_Config<"u"&&typeof TLDR_Chatbot_Config[e]=="object"&&(s=TLDR_Chatbot_Config[e]),s}class Qc{constructor(){cn(this,"_style",`
    .cb-launcher {
        width: 60px;
        height: 60px;
        background-position: center;
        background-repeat: no-repeat;
        position: fixed;
        right: 25px;
        bottom: 25px;
        border-radius: 50%;
        background-color: var(--launcher-bg-color);
        box-shadow: none;
        transition: box-shadow 0.2s ease-in-out;
        cursor: pointer;

        &:before {
            content: '';
            position: relative;
            display: block;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            transition: box-shadow 0.2s ease-in-out;
        }

        .cb-open-icon,
        .cb-closed-icon {
            width: 60px;
            height: 60px;
            position: fixed;
            right: 25px;
            bottom: 25px;
            transition: opacity 100ms ease-in-out, transform 100ms ease-in-out;
        }

        .cb-closed-icon {
            transition: opacity 100ms ease-in-out, transform 100ms ease-in-out;
            width: 60px;
            height: 60px;
        }

        .cb-open-icon {
            padding: 10px;
            box-sizing: border-box;
            opacity: 1;
        }

        &.opened .cb-open-icon {
            transform: rotate(-90deg);
            opacity: 1;
        }

        &.opened .cb-closed-icon {
            transform: rotate(-90deg);
            opacity: 1;
            padding: 20px;
            box-sizing: border-box;
        }

        &.opened:before {
            box-shadow: 0px 0px 400px 250px rgba(148, 149, 150, 0.2);
        }

        &:hover {
            box-shadow: 0 0px 27px 1.5px rgba(0, 0, 0, 0.2);
        }

        .cb-notification {
            position: absolute;
            top: -3px;
            left: 41px;
            display: flex;
            justify-content: center;
            flex-direction: column;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            background: #ff4646;
            margin: auto;
        }

        &.hidden {
            display: none;
        }
    }

    .cb-chat-window {
        height: calc(100% - 120px);
        max-height: 590px;
        position: fixed;
        right: 25px;
        bottom: 100px;
        overflow-x: hidden;
        box-sizing: border-box;
        box-shadow: 0px 7px 40px 2px rgba(148, 149, 150, 0.1);
        background: white;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        border-radius: 10px;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        animation: fadeIn;
        animation-duration: 0.3s;
        animation-timing-function: ease-in-out;

        &.compact {
            width: 370px;
        }

        &.large {
            width: 550px;
        }

        &.closed {
            opacity: 0;
            display: none;
            bottom: 90px;
        }
    }

    @keyframes fadeIn {
        0% {
            display: none;
            opacity: 0;
        }

        100% {
            display: flex;
            opacity: 1;
        }
    }

    @keyframes bob {
        10% {
            transform: translateY(-10px);
            background-color: #9e9da2;
        }

        50% {
            transform: translateY(0);
            background-color: #b6b5ba;
        }
    }

    @media (max-width: 450px) {
        .cb-chat-window {
            width: 100%;
            height: 100%;
            max-height: 100%;
            right: 0px;
            bottom: 0px;
            border-radius: 0px;
            transition: 0.1s ease-in-out;

            &.large, &.compact {
                width: 100%;
            }

            &.closed {
                bottom: 0px;
            }
        }
    }

    .cb-header {
        min-height: 75px;
        border-top-left-radius: 9px;
        border-top-right-radius: 9px;
        padding: 10px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        position: relative;
        box-sizing: border-box;
        display: flex;
        background-color: var(--header-bg-color);
        color: var(--header-txt-color);

        .cb-header--img {
            align-self: center;
            padding: 10px;
            max-width: 50px;
        }

        .cb-header--title {
            align-self: center;
            padding: 10px;
            flex: 1;
            user-select: none;
            font-size: 20px;

            sup {
                margin-left: 5px;
                font-size: 0.7rem;
                font-weight: 500;
                letter-spacing: 1px;
            }
        }

        .cb-header--title.enabled {
            cursor: pointer;
            border-radius: 5px;
        }

        .cb-header--title.enabled:hover {
            box-shadow: 0px 2px 5px rgba(0.2, 0.2, 0.5, 0.1);
        }

        .cb-header--close-button {
            width: 40px;
            align-self: center;
            height: 40px;
            margin-right: 10px;
            box-sizing: border-box;
            cursor: pointer;
            border-radius: 5px;
            margin-left: auto;
        }

        .cb-header--close-button:hover {
            box-shadow: 0px 2px 5px rgba(0.2, 0.2, 0.5, 0.1);
        }

        .cb-header--close-button img {
            width: 100%;
            height: 100%;
            padding: 13px;
            box-sizing: border-box;
        }
    }

    @media (max-width: 450px) {
        .cb-header {
            border-radius: 0px;
        }
    }

    .cb-processing {
        width: 100%;
        height: 4px;
        background: white;
        transform: translate(-50%, -50%);
    }

    .cb-processing::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--processing-bg-color);
        animation: processing 5s linear infinite;
    }

    @keyframes processing {
        0% {
            left: 0;
        }

        50% {
            left: 100%;
        }

        0% {
            left: 0;
        }
    }

    .cb-message-list {
        height: 80%;
        overflow-y: auto;
        background-size: 100%;
        padding: 40px 0px;
        scroll-behavior: smooth;
        background-color: var(--message-list-bg-color)
    }

    .cb-message {
        width: 85%;
        margin: auto;
        font-size: 1rem;
        line-height: 1.25rem;
        position: relative;
        padding-bottom: 10px;
        display: flex;

        .cb-message--content {
            width: 100%;
            display: flex;

            &.sent {
                justify-content: flex-end;

                .cb-message--text {
                    margin-left: 40px;
                    background-color: var(--message-sent-bg-color);
                    color: var(--message-sent-txt-color);
                }
            }

            &.received {
                .cb-message--text, .cb-message--typing {
                    margin-right: 40px;
                    background-color: var(--message-received-bg-color);
                    color: var(--message-received-txt-color);
                }
            }

            .cb-message--text {
                padding: 5px 20px;
                border-radius: 6px;
                font-weight: 300;
                position: relative;
                -webkit-font-smoothing: subpixel-antialiased;

                .cb-message--text-content {
                    white-space: wrap;

                    code {
                        font-family: 'Courier New', Courier, monospace !important;
                    }
                }

                .cb-additional-sources-title {
                    text-transform: uppercase;
                    font-size: 0.65rem;
                    font-weight: 500;
                    letter-spacing: 2px;
                    display: block;
                    width: 100%;
                    margin-top: 15px;
                }

                .cb-additional-sources-list {
                    padding-left: 25px;
                    list-style-type: "- ";
                    margin-top: 5px;
                    line-height: 1.25rem;

                    li {
                        a {
                            text-decoration: none;
                            color: var(--additional-source-txt-color);
                        }
                    }
                }
            }

            .cb-message--typing {
                text-align: center;
                padding: 17px 20px;
                border-radius: 6px;

                & span {
                    display: inline-block;
                    background-color: #b6b5ba;
                    width: 10px;
                    height: 10px;
                    border-radius: 100%;
                    margin-right: 3px;
                    animation: bob 2s infinite;
                }

                & span:nth-child(1) {
                    animation-delay: -1s;
                }

                & span:nth-child(2) {
                    animation-delay: -0.85s;
                }

                & span:nth-child(3) {
                    animation-delay: -0.7s;
                }
            }
        }

        &.cb-has-feedback {
            padding-bottom: 30px;
        }

        .cb-message-feedback {
            position: absolute;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 0 4px 10px;
            background-color: #F4F7E5;
            border-radius: 6px;
            bottom: 10px;
            width: 150px;
            right: 50px;

            & span {
                font-size: 0.7rem;
                font-weight: 300;
            }

            & img {
                display: inline;
                max-width: 22px;
            }

            .cb-feedback-action {
                cursor: pointer;
            }

            .cb-feedback-action.selected {
                max-width: 28px !important;
            }
        }
    }

    @media (max-width: 450px) {
        .cb-message {
            width: 80%;
        }
    }

    .cb-user-input {
        min-height: 55px;
        margin: 0px;
        position: relative;
        bottom: 0;
        display: flex;
        background-color: var(--input-bg-color);
        border-bottom-left-radius: 10px;
        border-bottom-right-radius: 10px;
        transition: background-color 0.2s ease, box-shadow 0.2s ease;

        &.active {
            box-shadow: none;
            box-shadow: 0px -5px 20px 0px rgba(150, 165, 190, 0.2);
        }

        .cb-user-input--text {
            width: calc(100% - 80px);
            resize: none;
            border: none;
            outline: none;
            border-bottom-left-radius: 10px;
            box-sizing: border-box;
            padding: 18px;
            font-size: 15px;
            font-weight: 400;
            line-height: 1.33;
            white-space: pre-wrap;
            word-wrap: break-word;
            color: #565867;
            -webkit-font-smoothing: antialiased;
            max-height: 200px;
            overflow: scroll;
            bottom: 0;
            overflow-x: hidden;
            overflow-y: auto;
            color: var(--input-txt-color);

            &:empty:before {
                content: attr(placeholder);
                display: block;
                filter: contrast(15%);
                outline: none;
                cursor: text;
            }
        }

        .cb-user-input--buttons {
            width: 80px;
            position: absolute;
            right: 30px;
            height: 100%;
            display: flex;
            gap: 10px;
            justify-content: flex-end;

            .cb-user-input--button {
                width: 30px;
                height: 100%;
                margin-left: 2px;
                margin-right: 2px;
                display: flex;
                cursor: pointer;
                flex-direction: column;
                justify-content: center;

                & img {
                    max-width: 22px;
                }
            }

            .tooltip {
                position: relative;
                display: inline-block;
            }

            .tooltip .tooltiptext {
                visibility: hidden;
                width: 120px;
                bottom: 100%;
                left: 50%;
                margin-left: -60px;
                margin-bottom: 10px;
                background-color: var(--tooltip-bg-color);
                color: var(--tooltip-txt-color);
                text-align: center;
                padding: 5px 0;
                border-radius: 6px;
                font-size: 0.8rem;

                position: absolute;
                z-index: 1;
            }

            .tooltip:hover .tooltiptext {
                visibility: visible;
            }

            .tooltip .tooltiptext::after {
                content: " ";
                position: absolute;
                top: 100%;
                left: 50%;
                margin-left: -5px;
                border-width: 5px;
                border-style: solid;
                border-color: var(--tooltip-bg-color) transparent transparent transparent;
            }
        }
    }`)}render(t){return(t.style||this._style).replaceAll("var(--launcher-bg-color)",t.launcher.bgColor).replaceAll("var(--header-bg-color)",t.header.bgColor).replaceAll("var(--header-txt-color)",t.header.txtColor).replaceAll("var(--message-list-bg-color)",t.messageList.bgColor).replaceAll("var(--message-sent-bg-color)",t.message.sent.bgColor).replaceAll("var(--message-sent-txt-color)",t.message.sent.txtColor).replaceAll("var(--message-received-bg-color)",t.message.received.bgColor).replaceAll("var(--message-received-txt-color)",t.message.received.txtColor).replaceAll("var(--input-bg-color)",t.input.bgColor).replaceAll("var(--input-txt-color)",t.input.txtColor).replaceAll("var(--tooltip-bg-color)",t.tooltip.bgColor).replaceAll("var(--tooltip-txt-color)",t.tooltip.txtColor).replaceAll("var(--processing-bg-color)",t.processing.bgColor).replaceAll("var(--additional-source-txt-color)",t.message.received.additionalSource.txtColor)}}const Pc={install(e,t){const s=e.config.globalProperties,n=kt(t,ds("theme"));s.$theme=n.skin;const o=new Qc,i=new CSSStyleSheet;i.replaceSync(o.render(n.skin)),t.container.adoptedStyleSheets=[i]}},$c={install(e,t={}){const s=e.config.globalProperties;s.$settings=kt(t,ds("app"));const n=s.$store.conversation,o={id:Tt(),role:"assistant",content:s.$i18n.conversation.greeting,type:"text"};n.messages.length===0&&(n.messages=[o]),s.$conversation={addMessage(i,r="user",l="text"){let u;typeof i=="object"?u=Object.assign({id:Tt(),role:r,type:l},i):u={id:Tt(),role:r,type:l,content:i},n.messages.push(u)},reset(){n.id=Tt(),n.messages=[o]},get messages(){return n.messages},get id(){return n.id}}}},Rc={install(e,t={}){const s=e.config.globalProperties,n=kt(t,ds("i18n"));s.$i18n=n[n.lang]||n.en,s.$i18n._=o=>{let i=o;if(o.indexOf("$i18n.")===0){const r=o.split(".").slice(1);let l=s.$i18n;for(let u of r)if(l=typeof l[u]<"u"?l[u]:null,l===null)break;i=l||""}return i}}},Fc={install(e,t={}){const s=e.config.globalProperties;s.$api=kt(t,ds("api"))}};var Bc=!1;/*!
 * pinia v2.1.7
 * (c) 2023 Eduardo San Martin Morote
 * @license MIT
 */let Ho;const gs=e=>Ho=e,Wo=Symbol();function ks(e){return e&&typeof e=="object"&&Object.prototype.toString.call(e)==="[object Object]"&&typeof e.toJSON!="function"}var Ot;(function(e){e.direct="direct",e.patchObject="patch object",e.patchFunction="patch function"})(Ot||(Ot={}));function Hc(){const e=Jn(!0),t=e.run(()=>go({}));let s=[],n=[];const o=rs({install(i){gs(o),o._a=i,i.provide(Wo,o),i.config.globalProperties.$pinia=o,n.forEach(r=>s.push(r)),n=[]},use(i){return!this._a&&!Bc?n.push(i):s.push(i),this},_p:s,_a:null,_e:e,_s:new Map,state:t});return o}const Zo=()=>{};function Pn(e,t,s,n=Zo){e.push(t);const o=()=>{const i=e.indexOf(t);i>-1&&(e.splice(i,1),n())};return!s&&Vn()&&pi(o),o}function it(e,...t){e.slice().forEach(s=>{s(...t)})}const Wc=e=>e();function Us(e,t){e instanceof Map&&t instanceof Map&&t.forEach((s,n)=>e.set(n,s)),e instanceof Set&&t instanceof Set&&t.forEach(e.add,e);for(const s in t){if(!t.hasOwnProperty(s))continue;const n=t[s],o=e[s];ks(o)&&ks(n)&&e.hasOwnProperty(s)&&!G(n)&&!Pe(n)?e[s]=Us(o,n):e[s]=n}return e}const Zc=Symbol();function Kc(e){return!ks(e)||!e.hasOwnProperty(Zc)}const{assign:ke}=Object;function Gc(e){return!!(G(e)&&e.effect)}function Jc(e,t,s,n){const{state:o,actions:i,getters:r}=t,l=s.state.value[e];let u;function f(){l||(s.state.value[e]=o?o():{});const g=Ri(s.state.value[e]);return ke(g,i,Object.keys(r||{}).reduce((b,D)=>(b[D]=rs(Bo(()=>{gs(s);const j=s._s.get(e);return r[D].call(j,j)})),b),{}))}return u=Ko(e,f,t,s,n,!0),u}function Ko(e,t,s={},n,o,i){let r;const l=ke({actions:{}},s),u={deep:!0};let f,g,b=[],D=[],j;const P=n.state.value[e];!i&&!P&&(n.state.value[e]={}),go({});let _;function Z(S){let k;f=g=!1,typeof S=="function"?(S(n.state.value[e]),k={type:Ot.patchFunction,storeId:e,events:j}):(Us(n.state.value[e],S),k={type:Ot.patchObject,payload:S,storeId:e,events:j});const te=_=Symbol();ho().then(()=>{_===te&&(f=!0)}),g=!0,it(b,k,n.state.value[e])}const q=i?function(){const{state:k}=s,te=k?k():{};this.$patch(fe=>{ke(fe,te)})}:Zo;function V(){r.stop(),b=[],D=[],n._s.delete(e)}function ee(S,k){return function(){gs(n);const te=Array.from(arguments),fe=[],ye=[];function tt(Y){fe.push(Y)}function xt(Y){ye.push(Y)}it(D,{args:te,name:S,store:K,after:tt,onError:xt});let ve;try{ve=k.apply(this&&this.$id===e?this:K,te)}catch(Y){throw it(ye,Y),Y}return ve instanceof Promise?ve.then(Y=>(it(fe,Y),Y)).catch(Y=>(it(ye,Y),Promise.reject(Y))):(it(fe,ve),ve)}}const L={_p:n,$id:e,$onAction:Pn.bind(null,D),$patch:Z,$reset:q,$subscribe(S,k={}){const te=Pn(b,S,k.detached,()=>fe()),fe=r.run(()=>Wt(()=>n.state.value[e],ye=>{(k.flush==="sync"?g:f)&&S({storeId:e,type:Ot.direct,events:j},ye)},ke({},u,k)));return te},$dispose:V},K=is(L);n._s.set(e,K);const pe=(n._a&&n._a.runWithContext||Wc)(()=>n._e.run(()=>(r=Jn()).run(t)));for(const S in pe){const k=pe[S];if(G(k)&&!Gc(k)||Pe(k))i||(P&&Kc(k)&&(G(k)?k.value=P[S]:Us(k,P[S])),n.state.value[e][S]=k);else if(typeof k=="function"){const te=ee(S,k);pe[S]=te,l.actions[S]=k}}return ke(K,pe),ke(v(K),pe),Object.defineProperty(K,"$state",{get:()=>n.state.value[e],set:S=>{Z(k=>{ke(k,S)})}}),n._p.forEach(S=>{ke(K,r.run(()=>S({store:K,app:n._a,pinia:n,options:l})))}),P&&i&&s.hydrate&&s.hydrate(K.$state,P),f=!0,g=!0,K}function Vc(e,t,s){let n,o;const i=typeof t=="function";typeof e=="string"?(n=e,o=i?s:t):(o=e,n=e.id);function r(l,u){const f=_r();return l=l||(f?jt(Wo,null):null),l&&gs(l),l=Ho,l._s.has(n)||(i?Ko(n,t,o,l):Jc(n,o,l)),l._s.get(n)}return r.$id=n,r}const Go=Hc(),Xc=Vc("app",{state:()=>{const e=localStorage.getItem("tldr-cb");let t={};return e&&(t=JSON.parse(e).conversation),t.origin!==location.origin&&(t={id:Tt(),origin:location.origin,messages:[]}),{hasNewMessage:!1,typing:!1,conversation:t}}});Go.use(({store:e})=>{e.$subscribe(()=>{localStorage.setItem("tldr-cb",JSON.stringify({conversation:e.$state.conversation}))})});const qc={install(e){e.use(Go),e.config.globalProperties.$store=Xc()}},$n="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTIxcHgiIGhlaWdodD0iNTNweCIgdmlld0JveD0iMCAwIDEyMSA1MyIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj4KICAgIDxnIGlkPSJQYWdlLTEiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPgogICAgICAgIDxwYXRoIGQ9Ik0xMy40NTgwNDg4LDQzLjg1ODUzNjYgQzEyLjA5MjE4ODMsNDMuODU4NTM2NiAxMC44NTMxNzYzLDQzLjcxMjE5NjYgOS43NDA5NzU2MSw0My40MTk1MTIyIEM4LjYyODc3NDkzLDQzLjEyNjgyNzggNy42NzI2ODY5Myw0Mi42MDk3NTk4IDYuODcyNjgyOTMsNDEuODY4MjkyNyBDNi4wNzI2Nzg5Myw0MS4xMjY4MjU2IDUuNDQ4Mjk0OTMsNDAuMTYwOTgxNiA0Ljk5OTUxMjIsMzguOTcwNzMxNyBDNC41NTA3Mjk0NiwzNy43ODA0ODE5IDQuMzI2MzQxNDYsMzYuMjY4MzAxOSA0LjMyNjM0MTQ2LDM0LjQzNDE0NjMgTDQuMzI2MzQxNDYsMTkuNTY1ODUzNyBMMC41OCwxOS41NjU4NTM3IEwwLjU4LDExLjk1NjA5NzYgTDQuMzI2MzQxNDYsMTEuOTU2MDk3NiBMNC4zMjYzNDE0NiwzLjkzNjU4NTM3IEwxMy4yMjM5MDI0LDMuOTM2NTg1MzcgTDEzLjIyMzkwMjQsMTEuOTU2MDk3NiBMMjAuNTk5NTEyMiwxMS45NTYwOTc2IEwyMC41OTk1MTIyLDE5LjU2NTg1MzcgTDEzLjIyMzkwMjQsMTkuNTY1ODUzNyBMMTMuMjIzOTAyNCwzMi45NzA3MzE3IEMxMy4yMjM5MDI0LDM1LjAwMDAxMDEgMTQuMTc5OTkwNCwzNi4wMTQ2MzQxIDE2LjA5MjE5NTEsMzYuMDE0NjM0MSBDMTcuNjUzMTc4NSwzNi4wMTQ2MzQxIDE5LjExNjU3ODUsMzUuNjQzOTA2MSAyMC40ODI0MzksMzQuOTAyNDM5IEwyMC40ODI0MzksNDIuMDQzOTAyNCBDMTkuNTQ1ODQ5LDQyLjU5MDI0NjYgMTguNTExNzEzLDQzLjAyOTI2NjYgMTcuMzgsNDMuMzYwOTc1NiBDMTYuMjQ4Mjg3LDQzLjY5MjY4NDYgMTQuOTQwOTgzLDQzLjg1ODUzNjYgMTMuNDU4MDQ4OCw0My44NTg1MzY2IFogTTI3LjU2NTM2NTksMC42IEwzNi40NjI5MjY4LDAuNiBMMzYuNDYyOTI2OCw0My4zMzE3MDczIEwyNy41NjUzNjU5LDQzLjMzMTcwNzMgTDI3LjU2NTM2NTksMC42IFogTTQyLjY2NzgwNDksNDkuMTI2ODI5MyBDNDYuODA0NDEwOSw0OC42OTc1NTg4IDQ4LjcxNjU4NjksNDYuNzY1ODcwOCA0OC40MDQzOTAyLDQzLjMzMTcwNzMgTDQ0Ljc3NTEyMiw0My4zMzE3MDczIEw0NC43NzUxMjIsMzMuOTY1ODUzNyBMNTQuMjU4MDQ4OCwzMy45NjU4NTM3IEw1NC4yNTgwNDg4LDQxLjg2ODI5MjcgQzU0LjI1ODA0ODgsNDUuNzMxNzI2NiA1My4zMjE0NzI4LDQ4LjUxMjE4NjYgNTEuNDQ4MjkyNyw1MC4yMDk3NTYxIEM0OS41NzUxMTI2LDUxLjkwNzMyNTYgNDYuOTAxOTY4Niw1Mi43NzU2MDk2IDQzLjQyODc4MDUsNTIuODE0NjM0MSBMNDIuNjY3ODA0OSw0OS4xMjY4MjkzIFogTTQ0Ljc3NTEyMiwxMS45NTYwOTc2IEw1NC4yNTgwNDg4LDExLjk1NjA5NzYgTDU0LjI1ODA0ODgsMjEuMzIxOTUxMiBMNDQuNzc1MTIyLDIxLjMyMTk1MTIgTDQ0Ljc3NTEyMiwxMS45NTYwOTc2IFogTTc3LjMyMTQ2MzQsMzYuMzY1ODUzNyBDNzguMzc1MTI3MiwzNi4zNjU4NTM3IDc5LjM2MDQ4MzIsMzYuMTYwOTc3NyA4MC4yNzc1NjEsMzUuNzUxMjE5NSBDODEuMTk0NjM4NywzNS4zNDE0NjE0IDgxLjk5NDYzMDcsMzQuNzU2MTAxNCA4Mi42Nzc1NjEsMzMuOTk1MTIyIEM4My4zNjA0OTEyLDMzLjIzNDE0MjUgODMuOTA2ODI3MiwzMi4zMTcwNzg1IDg0LjMxNjU4NTQsMzEuMjQzOTAyNCBDODQuNzI2MzQzNSwzMC4xNzA3MjYzIDg0LjkzMTIxOTUsMjguOTkwMjUwMyA4NC45MzEyMTk1LDI3LjcwMjQzOSBMODQuOTMxMjE5NSwyNy41ODUzNjU5IEM4NC45MzEyMTk1LDI2LjI5NzU1NDUgODQuNzI2MzQzNSwyNS4xMTcwNzg1IDg0LjMxNjU4NTQsMjQuMDQzOTAyNCBDODMuOTA2ODI3MiwyMi45NzA3MjYzIDgzLjM2MDQ5MTIsMjIuMDUzNjYyMyA4Mi42Nzc1NjEsMjEuMjkyNjgyOSBDODEuOTk0NjMwNywyMC41MzE3MDM1IDgxLjE5NDYzODcsMTkuOTQ2MzQzNSA4MC4yNzc1NjEsMTkuNTM2NTg1NCBDNzkuMzYwNDgzMiwxOS4xMjY4MjcyIDc4LjM3NTEyNzIsMTguOTIxOTUxMiA3Ny4zMjE0NjM0LDE4LjkyMTk1MTIgQzc2LjI2Nzc5OTYsMTguOTIxOTUxMiA3NS4yODI0NDM2LDE5LjEyNjgyNzIgNzQuMzY1MzY1OSwxOS41MzY1ODU0IEM3My40NDgyODgxLDE5Ljk0NjM0MzUgNzIuNjM4NTQwMSwyMC41MzE3MDM1IDcxLjkzNjA5NzYsMjEuMjkyNjgyOSBDNzEuMjMzNjU1LDIyLjA1MzY2MjMgNzAuNjc3NTYzLDIyLjk2MDk3MDMgNzAuMjY3ODA0OSwyNC4wMTQ2MzQxIEM2OS44NTgwNDY3LDI1LjA2ODI5OCA2OS42NTMxNzA3LDI2LjI1ODUzIDY5LjY1MzE3MDcsMjcuNTg1MzY1OSBMNjkuNjUzMTcwNywyNy43MDI0MzkgQzY5LjY1MzE3MDcsMjguOTkwMjUwMyA2OS44NTgwNDY3LDMwLjE3MDcyNjMgNzAuMjY3ODA0OSwzMS4yNDM5MDI0IEM3MC42Nzc1NjMsMzIuMzE3MDc4NSA3MS4yMzM2NTUsMzMuMjM0MTQyNSA3MS45MzYwOTc2LDMzLjk5NTEyMiBDNzIuNjM4NTQwMSwzNC43NTYxMDE0IDczLjQ0ODI4ODEsMzUuMzQxNDYxNCA3NC4zNjUzNjU5LDM1Ljc1MTIxOTUgQzc1LjI4MjQ0MzYsMzYuMTYwOTc3NyA3Ni4yNjc3OTk2LDM2LjM2NTg1MzcgNzcuMzIxNDYzNCwzNi4zNjU4NTM3IFogTTc1LjAzODUzNjYsNDMuOTE3MDczMiBDNzMuMjA0MzgxMSw0My45MTcwNzMyIDcxLjQyODc4OTEsNDMuNTY1ODU3MiA2OS43MTE3MDczLDQyLjg2MzQxNDYgQzY3Ljk5NDYyNTYsNDIuMTYwOTcyMSA2Ni40NzI2ODk2LDQxLjExNzA4MDEgNjUuMTQ1ODUzNywzOS43MzE3MDczIEM2My44MTkwMTc4LDM4LjM0NjMzNDUgNjIuNzU1NjEzOCwzNi42NDg3OTA1IDYxLjk1NTYwOTgsMzQuNjM5MDI0NCBDNjEuMTU1NjA1OCwzMi42MjkyNTgyIDYwLjc1NTYwOTgsMzAuMzE3MDg2MiA2MC43NTU2MDk4LDI3LjcwMjQzOSBMNjAuNzU1NjA5OCwyNy41ODUzNjU5IEM2MC43NTU2MDk4LDI0Ljk3MDcxODYgNjEuMTU1NjA1OCwyMi42NTg1NDY2IDYxLjk1NTYwOTgsMjAuNjQ4NzgwNSBDNjIuNzU1NjEzOCwxOC42MzkwMTQzIDYzLjgwOTI2MTgsMTYuOTQxNDcwMyA2NS4xMTY1ODU0LDE1LjU1NjA5NzYgQzY2LjQyMzkwOSwxNC4xNzA3MjQ4IDY3LjkzNjA4OSwxMy4xMjY4MzI4IDY5LjY1MzE3MDcsMTIuNDI0MzkwMiBDNzEuMzcwMjUyNSwxMS43MjE5NDc3IDczLjE2NTM1NjUsMTEuMzcwNzMxNyA3NS4wMzg1MzY2LDExLjM3MDczMTcgQzc3LjQxOTAzNjMsMTEuMzcwNzMxNyA3OS4zNzk5OTIzLDExLjgzOTAxOTcgODAuOTIxNDYzNCwxMi43NzU2MDk4IEM4Mi40NjI5MzQ1LDEzLjcxMjE5OTggODMuNzYwNDgyNSwxNC44MDQ4NzE4IDg0LjgxNDE0NjMsMTYuMDUzNjU4NSBMODQuODE0MTQ2MywwLjYgTDkzLjcxMTcwNzMsMC42IEw5My43MTE3MDczLDQzLjMzMTcwNzMgTDg0LjgxNDE0NjMsNDMuMzMxNzA3MyBMODQuODE0MTQ2MywzOC44MjQzOTAyIEM4My43MjE0NTgsNDAuMzA3MzI0NSA4Mi40MDQzOTgsNDEuNTI2ODI0NSA4MC44NjI5MjY4LDQyLjQ4MjkyNjggQzc5LjMyMTQ1NTcsNDMuNDM5MDI5MiA3Ny4zODAwMTE3LDQzLjkxNzA3MzIgNzUuMDM4NTM2Niw0My45MTcwNzMyIFogTTEwMS42NzI2ODMsMTEuOTU2MDk3NiBMMTEwLjU3MDI0NCwxMS45NTYwOTc2IEwxMTAuNTcwMjQ0LDE4LjI3ODA0ODggQzExMS40Njc4MDksMTYuMTMxNjk2NiAxMTIuNjk3MDY1LDE0LjQxNDY0MDYgMTE0LjI1ODA0OSwxMy4xMjY4MjkzIEMxMTUuODE5MDMyLDExLjgzOTAxOCAxMTcuOTI2MzI4LDExLjI1MzY1OCAxMjAuNTgsMTEuMzcwNzMxNyBMMTIwLjU4LDIwLjY3ODA0ODggTDEyMC4xMTE3MDcsMjAuNjc4MDQ4OCBDMTE3LjE0NTgzOSwyMC42NzgwNDg4IDExNC44MTQxNTUsMjEuNTc1NjAwOCAxMTMuMTE2NTg1LDIzLjM3MDczMTcgQzExMS40MTkwMTYsMjUuMTY1ODYyNiAxMTAuNTcwMjQ0LDI3Ljk1NjA3ODYgMTEwLjU3MDI0NCwzMS43NDE0NjM0IEwxMTAuNTcwMjQ0LDQzLjMzMTcwNzMgTDEwMS42NzI2ODMsNDMuMzMxNzA3MyBMMTAxLjY3MjY4MywxMS45NTYwOTc2IFoiIGlkPSJ0bDtkciIgZmlsbD0iI0RGRENGRiI+PC9wYXRoPgogICAgPC9nPgo8L3N2Zz4=",Rn="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTIxcHgiIGhlaWdodD0iMTIxcHgiIHZpZXdCb3g9IjAgMCAxMjEgMTIxIiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPGcgaWQ9IlBhZ2UtMSIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPGcgaWQ9ImtrIiBmaWxsLXJ1bGU9Im5vbnplcm8iIGZpbGw9IiNERkRDRkYiPgogICAgICAgICAgICA8cGF0aCBkPSJNMTE4LjIxMjQzNSwyLjkxNTMxMjU5IEMxMTUuNDU2ODUzLDAuMTQ5NTYyNDcxIDExMC45NjI1MDksMC4xNDk1NjI0NzEgMTA4LjE4NjU5MSwyLjkxNTMxMjU5IEw2MC4yOTQzNzM5LDUwLjgxNzY5NzkgTDEyLjQxMjMyNSwyLjkxNTMxMjU5IEM5LjY0NjU3NDg0LDAuMTQ5NTYyNDcxIDUuMTUyMjMwOSwwLjE0OTU2MjQ3MSAyLjM4NjQ4MDc5LDIuOTE1MzEyNTkgQy0wLjM3OTI2OTMzLDUuNjgxMDYyNyAtMC4zNzkyNjkzMywxMC4xNzU0MDY2IDIuMzg2NDgwNzksMTIuOTQxMTU2OCBMNTAuMjY4NTI5Nyw2MC44NDM1NDIgTDIuMzc2MzEyNTksMTA4Ljc0NTkyNyBDLTAuMzg5NDM3NTI5LDExMS41MDE1MDkgLTAuMzg5NDM3NTI5LDExNS45OTU4NTMgMi4zNzYzMTI1OSwxMTguNzcxNzcyIEMzLjc1OTE4NzY1LDEyMC4xNTQ2NDcgNS41NjkxMjcwNiwxMjAuODM1OTE2IDcuMzc5MDY2NDcsMTIwLjgzNTkxNiBDOS4xOTkxNzQwOSwxMjAuODM1OTE2IDExLjAwOTExMzUsMTIwLjE1NDY0NyAxMi4zOTE5ODg2LDExOC43NzE3NzIgTDYwLjI5NDM3MzksNzAuODY5Mzg2MiBMMTA4LjE4NjU5MSwxMTguNzcxNzcyIEMxMDkuNTc5NjM0LDEyMC4xNTQ2NDcgMTExLjM3OTQwNSwxMjAuODM1OTE2IDExMy4yMDk2ODEsMTIwLjgzNTkxNiBDMTE1LjAxOTYyMSwxMjAuODM1OTE2IDExNi44MTkzOTIsMTIwLjE1NDY0NyAxMTguMjEyNDM1LDExOC43NzE3NzIgQzEyMC45ODgzNTMsMTE1Ljk5NTg1MyAxMjAuOTg4MzUzLDExMS41MDE1MDkgMTE4LjIxMjQzNSwxMDguNzQ1OTI3IEw3MC4zMTAwNDk4LDYwLjg0MzU0MiBMMTE4LjIxMjQzNSwxMi45NDExNTY4IEMxMjAuOTk4NTIyLDEwLjE3NTQwNjYgMTIwLjk5ODUyMiw1LjY4MTA2MjcgMTE4LjIxMjQzNSwyLjkxNTMxMjU5IFoiIGlkPSJTaGFwZSI+PC9wYXRoPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+",eu="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHRpdGxlPnRodW1iLXVwPC90aXRsZT48cGF0aCBkPSJNMjMsMTBDMjMsOC44OSAyMi4xLDggMjEsOEgxNC42OEwxNS42NCwzLjQzQzE1LjY2LDMuMzMgMTUuNjcsMy4yMiAxNS42NywzLjExQzE1LjY3LDIuNyAxNS41LDIuMzIgMTUuMjMsMi4wNUwxNC4xNywxTDcuNTksNy41OEM3LjIyLDcuOTUgNyw4LjQ1IDcsOVYxOUEyLDIgMCAwLDAgOSwyMUgxOEMxOC44MywyMSAxOS41NCwyMC41IDE5Ljg0LDE5Ljc4TDIyLjg2LDEyLjczQzIyLjk1LDEyLjUgMjMsMTIuMjYgMjMsMTJWMTBNMSwyMUg1VjlIMVYyMVoiIC8+PC9zdmc+",tu="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNCAyNCI+PHRpdGxlPnRodW1iLWRvd248L3RpdGxlPjxwYXRoIGQ9Ik0xOSwxNUgyM1YzSDE5TTE1LDNINkM1LjE3LDMgNC40NiwzLjUgNC4xNiw0LjIyTDEuMTQsMTEuMjdDMS4wNSwxMS41IDEsMTEuNzQgMSwxMlYxNEEyLDIgMCAwLDAgMywxNkg5LjMxTDguMzYsMjAuNTdDOC4zNCwyMC42NyA4LjMzLDIwLjc3IDguMzMsMjAuODhDOC4zMywyMS4zIDguNSwyMS42NyA4Ljc3LDIxLjk0TDkuODMsMjNMMTYuNDEsMTYuNDFDMTYuNzgsMTYuMDUgMTcsMTUuNTUgMTcsMTVWNUMxNywzLjg5IDE2LjEsMyAxNSwzWiIgLz48L3N2Zz4=",su="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTE4cHgiIGhlaWdodD0iMTE4cHgiIHZpZXdCb3g9IjAgMCAxMTggMTE4IiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPGcgaWQ9IlBhZ2UtMSIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPGcgaWQ9ImFhIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMS4wMDAwMDAsIC0xLjAwMDAwMCkiIGZpbGwtcnVsZT0ibm9uemVybyIgZmlsbD0iI0RGRENGRiI+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik0xMTQuMTM1LDIuMDEgTDMuNTc3NSw0Ny45MSBDMC45NzUsNDkuMDEyNSAxLjIzLDUyLjg3NSA0LjA2NSw1My41MDUgTDU0Ljk3NSw2NS4wMjUgTDY2LjQ4NzUsMTE1LjkyNzUgQzY3LjExLDExOC43NTUgNzEuMDAyNSwxMTkuMDQgNzIuMDc1LDExNi40MTUgTDExNy45OTc1LDUuODY1IEMxMTguOTk1LDMuNDUgMTE2LjU0MjUsMS4wMDUgMTE0LjEzNSwyLjAxIEwxMTQuMTM1LDIuMDEgWiBNMTQuNCw0OS44MDc1IEwxMDIuNjksMTMuMTMyNSBMNTYuNTEyNSw1OS4zMTc1IEwxNC40LDQ5LjgwNzUgWiBNNzAuMTg1LDEwNS41ODUgTDYwLjY3NSw2My40ODc1IEwxMDYuODUyNSwxNy4zMSBMNzAuMTg1LDEwNS41ODUgWiIgaWQ9IlNoYXBlIj48L3BhdGg+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4=",nu="data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iOTdweCIgaGVpZ2h0PSIxMThweCIgdmlld0JveD0iMCAwIDk3IDExOCIgdmVyc2lvbj0iMS4xIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIj4KICAgIDxnIGlkPSJQYWdlLTEiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPgogICAgICAgIDxnIGlkPSJyZXN0YXJ0LWljb24iIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xLjAwMDAwMCwgMC4wMDAwMDApIiBmaWxsLXJ1bGU9Im5vbnplcm8iIGZpbGw9IiNERkRDRkYiPgogICAgICAgICAgICA8cGF0aCBkPSJNNDkuMzUyOTQ3NiwyMS4xNDAyNjM5IEM2Mi4wMzk1ODEyLDIxLjE0MDI2MzkgNzQuMTIyMDg5NCwyNS45NzMyNjcyIDgzLjE4Mzk3MDUsMzUuMDM1MTQ4NCBDMTAxLjkxMTg1OCw1My43NjMwMzYxIDEwMS45MTE4NTgsODQuNTczNDMyIDgzLjE4Mzk3MDUsMTAzLjMwMTMyIEM3Mi4zMDk3MTMyLDExNC43Nzk3MDIgNTcuMjA2NTc3OSwxMTkuMDA4NTggNDIuNzA3NTY4MSwxMTcuMTk2MjA0IEw0NS43MjgxOTUxLDEwNS4xMTM2OTYgQzU1Ljk5ODMyNzEsMTA2LjMyMTk0NyA2Ni44NzI1ODQ1LDEwMi42OTcxOTQgNzQuNzI2MjE0OCw5NC44NDM1NjM5IEM4OC42MjEwOTkyLDgwLjk0ODY3OTUgODguNjIxMDk5Miw1Ny45OTE5MTM5IDc0LjcyNjIxNDgsNDMuNDkyOTA0MSBDNjguMDgwODM1MywzNi44NDc1MjQ2IDU4LjQxNDgyODcsMzMuMjIyNzcyMSA0OS4zNTI5NDc2LDMzLjIyMjc3MjEgTDQ5LjM1Mjk0NzYsNjEuMDEyNTQxIEwxOS4xNDY2NzcxLDMwLjgwNjI3MDUgTDQ5LjM1Mjk0NzYsMC42IEw0OS4zNTI5NDc2LDIxLjE0MDI2MzkgTDQ5LjM1Mjk0NzYsMjEuMTQwMjYzOSBaIE0xNC45MTc3OTkyLDEwMy4zMDEzMiBDLTAuNzg5NDYxNDM4LDg3LjU5NDA1OSAtMy4yMDU5NjMwOCw2My40MjkwNDI2IDcuNjY4Mjk0Myw0NC43MDExNTQ5IEwxNi43MzAxNzU0LDUzLjc2MzAzNjEgQzEwLjA4NDc5NTksNjcuMDUzNzk1MSAxMi41MDEyOTc2LDgzLjk2OTMwNjYgMjMuOTc5NjgwNCw5NC44NDM1NjM5IEMyNy4wMDAzMDc0LDk3Ljg2NDE5MSAzMC42MjUwNTk5LDEwMC4yODA2OTMgMzQuODUzOTM3NywxMDIuMDkzMDY5IEwzMS4yMjkxODUzLDExNC4xNzU1NzcgQzI1LjE4NzkzMTIsMTExLjc1OTA3NSAxOS43NTA4MDI1LDEwOC4xMzQzMjMgMTQuOTE3Nzk5MiwxMDMuMzAxMzIgWiIgaWQ9IlNoYXBlIj48L3BhdGg+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4=",Jo=`cb-${(Math.random()+1).toString(36).substring(7)}`,Nt=zl(Yc),Ms=document.createElement("div");Ms.id=Jo;Ms.style="z-index: 99999; position: fixed";document.body.append(Ms);const Vo=Ms.attachShadow({mode:"open"});Nt.use(qc);Nt.use(Rc,{lang:"en",en:{name:"Chatbot",launcher:{icons:{openAlt:"Open Chatbot",closeAlt:"Close Chatbot"}},header:{prompt:"Chat with",icons:{logoAlt:"Friendly AI Chatbot",closeAlt:"Close Chatbot"}},message:{icons:{thumbUpAlt:"Yes! It was helpful",thumbDownAlt:"No! It was not helpful"},sourcesTitle:"Additional Resources:"},input:{placeholder:"Write something...",icons:{sendAlt:"Send Message",restartAlt:"Send Message"},tooltip:{sendBtn:"Send Message",restartBtn:"Restart Conversation"}},conversation:{greeting:"Howdy, there! How can I help you today?",unexpectedFailure:"Sorry, but I was having some troubles processing your message",delayInResponse:"Sorry for the delay. Give me few more seconds..."}}});Nt.use(Pc,{id:Jo,container:Vo,skin:{launcher:{bgColor:"#000000",icons:{openImg:$n,closeImg:Rn}},window:{size:"large"},processing:{bgColor:"#DFDCFF"},notification:{bgColor:"#ff4646"},header:{bgColor:"#000000",txtColor:"#FFFFFF",icons:{logoImg:$n,closeImg:Rn}},messageList:{bgColor:"#FFFFFF"},message:{sent:{bgColor:"#DFDCFF",txtColor:"#000000"},received:{bgColor:"#F0F0F0",txtColor:"#000000",additionalSource:{txtColor:"#365CD9"}},icons:{thumbUpImg:eu,thumbDownImg:tu}},input:{bgColor:"#222222",txtColor:"#FFFFFF",icons:{sendImg:su,restartImg:nu}},tooltip:{bgColor:"#000000",txtColor:"#FFFFFF"}}});Nt.use(Fc,{post:(e,t)=>{setTimeout(function(){t({answer:"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Duis tincidunt, mauris vel eleifend hendrerit, diam ante gravida ex, et gravida velit est ut mauris. Donec tempor at libero et volutpat.",type:"answer",sources:[{title:"Lorem ipsum dolor sit amet, consectetur adipiscing elit",link:"https://sample-domain.xyz"},{title:"Etiam dapibus sit amet ipsum vestibulum luctus",link:"https://sample-domain.xyz"}]})},3e3)},update:(e,t=null)=>{setTimeout(function(){console.log("conversation updated")},3e3)}});Nt.use($c,{thresholds:{8:{action:"showTyping"},13:{action:"hideTyping"},15:{action:"showWaiting",message:"$i18n.conversation.delayInResponse"},22:{action:"halt",message:"$i18n.conversation.unexpectedFailure"}},captureFeedback:!0,hideLauncher:!1});Nt.mount(Vo);