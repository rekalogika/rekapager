/*
 * This file is part of rekalogika/rekapager package.
 *
 * (c) Priyadi Iman Nurcahyo <https://rekalogika.dev>
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 */

'use strict';

function _typeof(o) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (o) { return typeof o; } : function (o) { return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o; }, _typeof(o); }
function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }
function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }
function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }
function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }
function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }
function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) arr2[i] = arr[i]; return arr2; }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _callSuper(t, o, e) { return o = _getPrototypeOf(o), _possibleConstructorReturn(t, _isNativeReflectConstruct() ? Reflect.construct(o, e || [], _getPrototypeOf(t).constructor) : o.apply(t, e)); }
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _isNativeReflectConstruct() { try { var t = !Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); } catch (t) {} return (_isNativeReflectConstruct = function _isNativeReflectConstruct() { return !!t; })(); }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(t) { var i = _toPrimitive(t, "string"); return "symbol" == _typeof(i) ? i : i + ""; }
function _toPrimitive(t, r) { if ("object" != _typeof(t) || !t) return t; var e = t[Symbol.toPrimitive]; if (void 0 !== e) { var i = e.call(t, r || "default"); if ("object" != _typeof(i)) return i; throw new TypeError("@@toPrimitive must return a primitive value."); } return ("string" === r ? String : Number)(t); }
import { Controller } from '@hotwired/stimulus';
var _default = /*#__PURE__*/function (_Controller) {
  function _default() {
    _classCallCheck(this, _default);
    return _callSuper(this, _default, arguments);
  }
  _inherits(_default, _Controller);
  return _createClass(_default, [{
    key: "connect",
    value: function connect() {
      var mediaQuery = '(min-width: ' + this.breakpointValue + ')';
      if (window.matchMedia(mediaQuery).matches) {
        return;
      }
      var href = this.getNextHref(document);
      if (href) {
        this.href = href;
        window.addEventListener('scroll', this.onScroll.bind(this));
      }
    }
  }, {
    key: "getNextHref",
    value: function getNextHref(node) {
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
    }
  }, {
    key: "onScroll",
    value: function onScroll() {
      if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 1) {
        this.goFetch();
      }
    }
  }, {
    key: "goFetch",
    value: function goFetch() {
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
        (_document$querySelect = document.querySelector('[data-controller~="' + _this.controllerNameValue + '"]')).append.apply(_document$querySelect, _toConsumableArray(newElements));

        // find next link

        if (href = _this.getNextHref(fragment)) {
          _this.href = href;
          window.addEventListener('scroll', _this.onScroll.bind(_this));
        }
      });
    }
  }]);
}(Controller);
_defineProperty(_default, "values", {
  breakpoint: {
    type: String,
    "default": '576px'
  },
  pagination: {
    type: String,
    "default": '.pagination'
  },
  controllerName: {
    type: String,
    "default": 'rekalogika--rekapager-bundle--infinite-scrolling'
  }
});
export { _default as default };