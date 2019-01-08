(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory() :
  typeof define === 'function' && define.amd ? define(factory) :
  (factory());
}(this, (function () { 'use strict';

  /* eslint-disable github/no-flowfixme */

  function createNode(text) {
    var node = document.createElement('pre');
    node.style.width = '1px';
    node.style.height = '1px';
    node.style.position = 'fixed';
    node.style.top = '5px';
    node.textContent = text;
    return node;
  }

  function copyNode(button, node) {
    if (writeAsync(button, node.textContent)) return;

    var selection = getSelection();
    if (selection == null) {
      return;
    }

    selection.removeAllRanges();

    var range = document.createRange();
    range.selectNodeContents(node);
    selection.addRange(range);

    document.execCommand('copy');
    selection.removeAllRanges();
  }

  function copyText(button, text) {
    if (writeAsync(button, text)) return;

    var body = document.body;
    if (!body) return;

    var node = createNode(text);
    body.appendChild(node);
    copyNode(button, node);
    body.removeChild(node);
  }

  function copyInput(button, node) {
    if (writeAsync(button, node.value)) return;

    node.select();
    document.execCommand('copy');
    var selection = getSelection();
    if (selection != null) {
      selection.removeAllRanges();
    }
  }

  function writeAsync(button, text) {
    // $FlowFixMe Clipboard is not defined in Flow yet.
    var clipboard = navigator.clipboard;
    if (!clipboard) return false;

    clipboard.writeText(text).then(function () {
      button.dispatchEvent(new CustomEvent('copy', { bubbles: true }));
    });
    return true;
  }

  var classCallCheck = function (instance, Constructor) {
    if (!(instance instanceof Constructor)) {
      throw new TypeError("Cannot call a class as a function");
    }
  };

  var createClass = function () {
    function defineProperties(target, props) {
      for (var i = 0; i < props.length; i++) {
        var descriptor = props[i];
        descriptor.enumerable = descriptor.enumerable || false;
        descriptor.configurable = true;
        if ("value" in descriptor) descriptor.writable = true;
        Object.defineProperty(target, descriptor.key, descriptor);
      }
    }

    return function (Constructor, protoProps, staticProps) {
      if (protoProps) defineProperties(Constructor.prototype, protoProps);
      if (staticProps) defineProperties(Constructor, staticProps);
      return Constructor;
    };
  }();

  var inherits = function (subClass, superClass) {
    if (typeof superClass !== "function" && superClass !== null) {
      throw new TypeError("Super expression must either be null or a function, not " + typeof superClass);
    }

    subClass.prototype = Object.create(superClass && superClass.prototype, {
      constructor: {
        value: subClass,
        enumerable: false,
        writable: true,
        configurable: true
      }
    });
    if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass;
  };

  var possibleConstructorReturn = function (self, call) {
    if (!self) {
      throw new ReferenceError("this hasn't been initialised - super() hasn't been called");
    }

    return call && (typeof call === "object" || typeof call === "function") ? call : self;
  };

  function _CustomElement() {
    return Reflect.construct(HTMLElement, [], this.__proto__.constructor);
  }
  Object.setPrototypeOf(_CustomElement.prototype, HTMLElement.prototype);
  Object.setPrototypeOf(_CustomElement, HTMLElement);

  function copy(button) {
    var id = button.getAttribute('for');
    var text = button.getAttribute('value');
    if (text) {
      copyText(button, text);
    } else if (id) {
      copyTarget(button, id);
    }
  }

  function copyTarget(button, id) {
    var content = button.ownerDocument.getElementById(id);
    if (!content) return;

    if (content instanceof HTMLInputElement || content instanceof HTMLTextAreaElement) {
      if (content.type === 'hidden') {
        copyText(button, content.value);
      } else {
        copyInput(button, content);
      }
    } else if (content instanceof HTMLAnchorElement && content.hasAttribute('href')) {
      copyText(button, content.href);
    } else {
      copyNode(button, content);
    }
  }

  function clicked(event) {
    var button = event.currentTarget;
    if (button instanceof HTMLElement) {
      copy(button);
    }
  }

  function keydown(event) {
    if (event.key === ' ' || event.key === 'Enter') {
      var button = event.currentTarget;
      if (button instanceof HTMLElement) {
        event.preventDefault();
        copy(button);
      }
    }
  }

  function focused(event) {
    event.currentTarget.addEventListener('keydown', keydown);
  }

  function blurred(event) {
    event.currentTarget.removeEventListener('keydown', keydown);
  }

  var ClipboardCopyElement = function (_CustomElement2) {
    inherits(ClipboardCopyElement, _CustomElement2);

    function ClipboardCopyElement() {
      classCallCheck(this, ClipboardCopyElement);

      var _this = possibleConstructorReturn(this, (ClipboardCopyElement.__proto__ || Object.getPrototypeOf(ClipboardCopyElement)).call(this));

      _this.addEventListener('click', clicked);
      _this.addEventListener('focus', focused);
      _this.addEventListener('blur', blurred);
      return _this;
    }

    createClass(ClipboardCopyElement, [{
      key: 'connectedCallback',
      value: function connectedCallback() {
        if (!this.hasAttribute('tabindex')) {
          this.setAttribute('tabindex', '0');
        }

        if (!this.hasAttribute('role')) {
          this.setAttribute('role', 'button');
        }
      }
    }, {
      key: 'value',
      get: function get$$1() {
        return this.getAttribute('value') || '';
      },
      set: function set$$1(text) {
        this.setAttribute('value', text);
      }
    }]);
    return ClipboardCopyElement;
  }(_CustomElement);

  if (!window.customElements.get('clipboard-copy')) {
    window.ClipboardCopyElement = ClipboardCopyElement;
    window.customElements.define('clipboard-copy', ClipboardCopyElement);
  }

})));
