/*
 * This file is part of rekalogika/rekapager package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

'use strict';

function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(t, e) { if (e && ("object" == typeof e || "function" == typeof e)) return e; if (void 0 !== e) throw new TypeError("Derived constructors may only return object or undefined"); return _assertThisInitialized(t); }
function _assertThisInitialized(e) { if (void 0 === e) throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); return e; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(t) { return _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function (t) { return t.__proto__ || Object.getPrototypeOf(t); }, _getPrototypeOf(t); }
function _inheritsLoose(t, o) { t.prototype = Object.create(o.prototype), t.prototype.constructor = t, _setPrototypeOf(t, o); }
function _setPrototypeOf(t, e) { return _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function (t, e) { return t.__proto__ = e, t; }, _setPrototypeOf(t, e); }
import { Controller } from '@hotwired/stimulus';
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    return _callSuper(this, _default, arguments);
  }
  _inheritsLoose(_default, _Controller);
  var _proto = _default.prototype;
  _proto.connect = function connect() {
    var mediaQuery = '(min-width: ' + this.breakpointValue + ')';
    if (window.matchMedia(mediaQuery).matches) {
      return;
    }
    var href = this.getNextHref(document);
    if (href) {
      this.href = href;
      window.addEventListener('scroll', this.onScroll.bind(this));
    }
  };
  _proto.getNextHref = function getNextHref(node) {
    var paginations = node.querySelectorAll(this.paginationValue);
    if (paginations.length == 0) {
      console.log('Error: no pagination element found');
      return null;
    } else if (paginations.length > 1) {
      console.log('Error: more than one pagination found');
      return null;
    }
    var pagination = paginations[0];
    pagination.remove();
    var next = pagination.querySelector('[rel="next"]');
    if (!next) {
      console.log('Error: no next link found');
      return null;
    }
    var href = next.getAttribute('href');
    if (!href || href == '#') {
      return null;
    }
    return href;
  };
  _proto.onScroll = function onScroll() {
    if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 1) {
      this.goFetch();
    }
  };
  _proto.goFetch = function goFetch() {
    var _this = this;
    // disable scroll event to prevent multiple fetch
    window.removeEventListener('scroll', this.onScroll);
    var href = this.href;
    if (!href) {
      return;
    }
    this.href = null;
    fetch(href).then(function (response) {
      return response.text();
    }).then(function (html) {
      var _document$querySelect;
      // append new elements

      var fragment = document.createRange().createContextualFragment(html);
      var newElements = fragment.querySelector('[data-controller~="' + _this.controllerNameValue + '"]').children;
      (_document$querySelect = document.querySelector('[data-controller~="' + _this.controllerNameValue + '"]')).append.apply(_document$querySelect, newElements);

      // find next link

      if (href = _this.getNextHref(fragment)) {
        _this.href = href;
        window.addEventListener('scroll', _this.onScroll.bind(_this));
      }
    });
  };
  return _default;
}(Controller);
_default.values = {
  breakpoint: {
    type: String,
    "default": '768px'
  },
  pagination: {
    type: String,
    "default": '.pagination'
  },
  controllerName: {
    type: String,
    "default": 'rekalogika--rekapager-bundle--infinite-scrolling'
  }
};
export { _default as default };