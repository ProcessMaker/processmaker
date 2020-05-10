webpackJsonp([91],{

/***/ "./node_modules/babel-loader/lib/index.js?{\"cacheDirectory\":true,\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":[\"> 2%\"],\"uglify\":true}}]],\"plugins\":[\"transform-object-rest-spread\",[\"transform-runtime\",{\"polyfill\":false,\"helpers\":false}]],\"babelrc\":false,\"env\":{\"test\":{\"presets\":[[\"env\",{\"targets\":{\"node\":\"current\"}}]]},\"development\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]},\"production\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]}}}!./node_modules/vue-loader/lib/selector.js?type=script&index=0!./resources/js/processes/modeler/print/PrintableDiagram.vue":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__components_ElementDoc__ = __webpack_require__("./resources/js/processes/modeler/print/components/ElementDoc.vue");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__components_ElementDoc___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0__components_ElementDoc__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__parseBpmnDocumentation__ = __webpack_require__("./resources/js/processes/modeler/print/parseBpmnDocumentation/index.js");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//




/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'PrintableDiagram',
  props: ['processName', 'updatedAt', 'author', 'svg', 'bpmn'],
  components: {
    ElementDoc: __WEBPACK_IMPORTED_MODULE_0__components_ElementDoc___default.a
  },
  data: function data() {
    return {
      bpmnString: this.bpmn,
      documentNodes: Object(__WEBPACK_IMPORTED_MODULE_1__parseBpmnDocumentation__["a" /* default */])(this.bpmn)
    };
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js?{\"cacheDirectory\":true,\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":[\"> 2%\"],\"uglify\":true}}]],\"plugins\":[\"transform-object-rest-spread\",[\"transform-runtime\",{\"polyfill\":false,\"helpers\":false}]],\"babelrc\":false,\"env\":{\"test\":{\"presets\":[[\"env\",{\"targets\":{\"node\":\"current\"}}]]},\"development\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]},\"production\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]}}}!./node_modules/vue-loader/lib/selector.js?type=script&index=0!./resources/js/processes/modeler/print/components/ElementDoc.vue":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__icons__ = __webpack_require__("./resources/js/processes/modeler/print/icons/index.js");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//



/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'ElementDoc',
  props: ['bpmnNode'],
  computed: {
    hasBodyText: function hasBodyText() {
      return !!this.bpmnNode.textHtml || !!this.bpmnNode.documentationHtml;
    },
    icon: function icon() {
      var knownIcon = __WEBPACK_IMPORTED_MODULE_0__icons__["a" /* default */][this.bpmnNode.type];
      if (knownIcon) {
        return knownIcon;
      }
      return { type: 'unknown' };
    }
  }
});

/***/ }),

/***/ "./node_modules/vue-loader/lib/component-normalizer.js":
/***/ (function(module, exports) {

/* globals __VUE_SSR_CONTEXT__ */

// IMPORTANT: Do NOT use ES2015 features in this file.
// This module is a runtime utility for cleaner component module output and will
// be included in the final webpack user bundle.

module.exports = function normalizeComponent (
  rawScriptExports,
  compiledTemplate,
  functionalTemplate,
  injectStyles,
  scopeId,
  moduleIdentifier /* server only */
) {
  var esModule
  var scriptExports = rawScriptExports = rawScriptExports || {}

  // ES6 modules interop
  var type = typeof rawScriptExports.default
  if (type === 'object' || type === 'function') {
    esModule = rawScriptExports
    scriptExports = rawScriptExports.default
  }

  // Vue.extend constructor export interop
  var options = typeof scriptExports === 'function'
    ? scriptExports.options
    : scriptExports

  // render functions
  if (compiledTemplate) {
    options.render = compiledTemplate.render
    options.staticRenderFns = compiledTemplate.staticRenderFns
    options._compiled = true
  }

  // functional template
  if (functionalTemplate) {
    options.functional = true
  }

  // scopedId
  if (scopeId) {
    options._scopeId = scopeId
  }

  var hook
  if (moduleIdentifier) { // server build
    hook = function (context) {
      // 2.3 injection
      context =
        context || // cached call
        (this.$vnode && this.$vnode.ssrContext) || // stateful
        (this.parent && this.parent.$vnode && this.parent.$vnode.ssrContext) // functional
      // 2.2 with runInNewContext: true
      if (!context && typeof __VUE_SSR_CONTEXT__ !== 'undefined') {
        context = __VUE_SSR_CONTEXT__
      }
      // inject component styles
      if (injectStyles) {
        injectStyles.call(this, context)
      }
      // register component module identifier for async chunk inferrence
      if (context && context._registeredComponents) {
        context._registeredComponents.add(moduleIdentifier)
      }
    }
    // used by ssr in case component is cached and beforeCreate
    // never gets called
    options._ssrRegister = hook
  } else if (injectStyles) {
    hook = injectStyles
  }

  if (hook) {
    var functional = options.functional
    var existing = functional
      ? options.render
      : options.beforeCreate

    if (!functional) {
      // inject component registration as beforeCreate hook
      options.beforeCreate = existing
        ? [].concat(existing, hook)
        : [hook]
    } else {
      // for template-only hot-reload because in that case the render fn doesn't
      // go through the normalizer
      options._injectStyles = hook
      // register for functioal component in vue file
      options.render = function renderWithStyleInjection (h, context) {
        hook.call(context)
        return existing(h, context)
      }
    }
  }

  return {
    esModule: esModule,
    exports: scriptExports,
    options: options
  }
}


/***/ }),

/***/ "./node_modules/vue-loader/lib/template-compiler/index.js?{\"id\":\"data-v-6a3be017\",\"hasScoped\":false,\"buble\":{\"transforms\":{}}}!./node_modules/vue-loader/lib/selector.js?type=template&index=0!./resources/js/processes/modeler/print/PrintableDiagram.vue":
/***/ (function(module, exports, __webpack_require__) {

var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    { staticClass: "page-content ml-2", attrs: { id: "processPrint" } },
    [
      _c("h1", [_vm._v(_vm._s(_vm.processName))]),
      _vm._v(" "),
      _c("p", [
        _c("i", { staticClass: "far fa-clock" }),
        _vm._v(
          " Updated " + _vm._s(_vm.updatedAt) + " by " + _vm._s(_vm.author)
        )
      ]),
      _vm._v(" "),
      _c(
        "div",
        {
          staticClass: "printable-svg bg-white w-75 m-auto mb-5",
          attrs: { id: "diagramContainer" }
        },
        [_c("div", { domProps: { innerHTML: _vm._s(_vm.svg) } })]
      ),
      _vm._v(" "),
      _c("h4", { staticClass: "mt-5" }, [_vm._v("Process Elements")]),
      _vm._v(" "),
      _c(
        "div",
        { attrs: { id: "diagram-documentation" } },
        _vm._l(_vm.documentNodes, function(node) {
          return _c("ElementDoc", { key: node.id, attrs: { bpmnNode: node } })
        }),
        1
      )
    ]
  )
}
var staticRenderFns = []
render._withStripped = true
module.exports = { render: render, staticRenderFns: staticRenderFns }
if (false) {
  module.hot.accept()
  if (module.hot.data) {
    require("vue-hot-reload-api")      .rerender("data-v-6a3be017", module.exports)
  }
}

/***/ }),

/***/ "./node_modules/vue-loader/lib/template-compiler/index.js?{\"id\":\"data-v-d066098c\",\"hasScoped\":false,\"buble\":{\"transforms\":{}}}!./node_modules/vue-loader/lib/selector.js?type=template&index=0!./resources/js/processes/modeler/print/components/ElementDoc.vue":
/***/ (function(module, exports, __webpack_require__) {

var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { staticClass: "card mb-3 mr-2 ml-2" }, [
    _c("div", { staticClass: "card-header" }, [
      _vm.icon.type === "image"
        ? _c("img", _vm._b({ attrs: { height: "14" } }, "img", _vm.icon, false))
        : _vm.icon.type === "icon"
        ? _c("i", _vm._b({}, "i", _vm.icon, false))
        : _vm._e(),
      _vm._v(" "),
      _c("strong", [_vm._v(_vm._s(_vm.bpmnNode.name))]),
      _vm._v("\n        (" + _vm._s(_vm.bpmnNode.id) + ")\n    ")
    ]),
    _vm._v(" "),
    _vm.hasBodyText
      ? _c("div", { staticClass: "card-body" }, [
          _vm.bpmnNode.textHtml
            ? _c("span", {
                domProps: { innerHTML: _vm._s(_vm.bpmnNode.textHtml) }
              })
            : _vm._e(),
          _vm._v(" "),
          _vm.bpmnNode.documentationHtml
            ? _c("span", {
                domProps: { innerHTML: _vm._s(_vm.bpmnNode.documentationHtml) }
              })
            : _vm._e()
        ])
      : _c("div", { staticClass: "card-body text-secondary" }, [
          _vm._v("No Documentation Found.")
        ])
  ])
}
var staticRenderFns = []
render._withStripped = true
module.exports = { render: render, staticRenderFns: staticRenderFns }
if (false) {
  module.hot.accept()
  if (module.hot.data) {
    require("vue-hot-reload-api")      .rerender("data-v-d066098c", module.exports)
  }
}

/***/ }),

/***/ "./resources/js/processes/modeler/print/PrintableDiagram.vue":
/***/ (function(module, exports, __webpack_require__) {

var disposed = false
var normalizeComponent = __webpack_require__("./node_modules/vue-loader/lib/component-normalizer.js")
/* script */
var __vue_script__ = __webpack_require__("./node_modules/babel-loader/lib/index.js?{\"cacheDirectory\":true,\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":[\"> 2%\"],\"uglify\":true}}]],\"plugins\":[\"transform-object-rest-spread\",[\"transform-runtime\",{\"polyfill\":false,\"helpers\":false}]],\"babelrc\":false,\"env\":{\"test\":{\"presets\":[[\"env\",{\"targets\":{\"node\":\"current\"}}]]},\"development\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]},\"production\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]}}}!./node_modules/vue-loader/lib/selector.js?type=script&index=0!./resources/js/processes/modeler/print/PrintableDiagram.vue")
/* template */
var __vue_template__ = __webpack_require__("./node_modules/vue-loader/lib/template-compiler/index.js?{\"id\":\"data-v-6a3be017\",\"hasScoped\":false,\"buble\":{\"transforms\":{}}}!./node_modules/vue-loader/lib/selector.js?type=template&index=0!./resources/js/processes/modeler/print/PrintableDiagram.vue")
/* template functional */
var __vue_template_functional__ = false
/* styles */
var __vue_styles__ = null
/* scopeId */
var __vue_scopeId__ = null
/* moduleIdentifier (server only) */
var __vue_module_identifier__ = null
var Component = normalizeComponent(
  __vue_script__,
  __vue_template__,
  __vue_template_functional__,
  __vue_styles__,
  __vue_scopeId__,
  __vue_module_identifier__
)
Component.options.__file = "resources/js/processes/modeler/print/PrintableDiagram.vue"

/* hot reload */
if (false) {(function () {
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  module.hot.accept()
  if (!module.hot.data) {
    hotAPI.createRecord("data-v-6a3be017", Component.options)
  } else {
    hotAPI.reload("data-v-6a3be017", Component.options)
  }
  module.hot.dispose(function (data) {
    disposed = true
  })
})()}

module.exports = Component.exports


/***/ }),

/***/ "./resources/js/processes/modeler/print/components/ElementDoc.vue":
/***/ (function(module, exports, __webpack_require__) {

var disposed = false
var normalizeComponent = __webpack_require__("./node_modules/vue-loader/lib/component-normalizer.js")
/* script */
var __vue_script__ = __webpack_require__("./node_modules/babel-loader/lib/index.js?{\"cacheDirectory\":true,\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":[\"> 2%\"],\"uglify\":true}}]],\"plugins\":[\"transform-object-rest-spread\",[\"transform-runtime\",{\"polyfill\":false,\"helpers\":false}]],\"babelrc\":false,\"env\":{\"test\":{\"presets\":[[\"env\",{\"targets\":{\"node\":\"current\"}}]]},\"development\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]},\"production\":{\"presets\":[[\"env\",{\"modules\":false,\"targets\":{\"browsers\":\"> 2%\",\"uglify\":true}}]]}}}!./node_modules/vue-loader/lib/selector.js?type=script&index=0!./resources/js/processes/modeler/print/components/ElementDoc.vue")
/* template */
var __vue_template__ = __webpack_require__("./node_modules/vue-loader/lib/template-compiler/index.js?{\"id\":\"data-v-d066098c\",\"hasScoped\":false,\"buble\":{\"transforms\":{}}}!./node_modules/vue-loader/lib/selector.js?type=template&index=0!./resources/js/processes/modeler/print/components/ElementDoc.vue")
/* template functional */
var __vue_template_functional__ = false
/* styles */
var __vue_styles__ = null
/* scopeId */
var __vue_scopeId__ = null
/* moduleIdentifier (server only) */
var __vue_module_identifier__ = null
var Component = normalizeComponent(
  __vue_script__,
  __vue_template__,
  __vue_template_functional__,
  __vue_styles__,
  __vue_scopeId__,
  __vue_module_identifier__
)
Component.options.__file = "resources/js/processes/modeler/print/components/ElementDoc.vue"

/* hot reload */
if (false) {(function () {
  var hotAPI = require("vue-hot-reload-api")
  hotAPI.install(require("vue"), false)
  if (!hotAPI.compatible) return
  module.hot.accept()
  if (!module.hot.data) {
    hotAPI.createRecord("data-v-d066098c", Component.options)
  } else {
    hotAPI.reload("data-v-d066098c", Component.options)
  }
  module.hot.dispose(function (data) {
    disposed = true
  })
})()}

module.exports = Component.exports


/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/annotations.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var annotations = {
  'bpmn:textAnnotation': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMThweCIgaGVpZ2h0PSIxOHB4IiB2aWV3Qm94PSIwIDAgMTggMTgiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8IS0tIEdlbmVyYXRvcjogU2tldGNoIDQ5LjMgKDUxMTY3KSAtIGh0dHA6Ly93d3cuYm9oZW1pYW5jb2RpbmcuY29tL3NrZXRjaCAtLT4KICAgIDx0aXRsZT5pY29ucy9wcm9jZXNzYnVpbGRlci90ZXh0LWFubm90YXRpb248L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZGVmcz48L2RlZnM+CiAgICA8ZyBpZD0iU3ltYm9scyIgc3Ryb2tlPSJub25lIiBzdHJva2Utd2lkdGg9IjEiIGZpbGw9Im5vbmUiIGZpbGwtcnVsZT0iZXZlbm9kZCI+CiAgICAgICAgPGcgaWQ9IkJ1aWxkL0FwcGxpY2F0aW9ucy9Qcm9jZXNzZXMvUHJvY2Vzc0J1aWxkZXIiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0xNC4wMDAwMDAsIC01NzMuMDAwMDAwKSIgZmlsbD0iIzc4ODc5MyI+CiAgICAgICAgICAgIDxnIGlkPSIxOCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoMC4wMDAwMDAsIDU2NS4wMDAwMDApIj4KICAgICAgICAgICAgICAgIDxnIGlkPSJpY29ucy9wcm9jZXNzYnVpbGRlci90ZXh0LWFubm90YXRpb24iIHRyYW5zZm9ybT0idHJhbnNsYXRlKDE0LjAwMDAwMCwgNy4wMDAwMDApIj4KICAgICAgICAgICAgICAgICAgICA8cGF0aCBkPSJNMi45NDU0MzUsMi45NjM2NjgxMiBDMy40ODc3NzEyLDIuOTYzNjY4MTIgMy45MjczMzYyMywyLjUyNDIzNzQzIDMuOTI3MzM2MjMsMS45ODE5MDEyMyBDMy45MjczMzYyMywxLjQzOTU2NTAzIDMuNDg3OTA1NTQsMSAyLjk0NTQzNSwxIEwwLjk4MTkwMTIyOSwxIEMwLjQzOTU2NTAzMiwxIDAsMS40Mzk1NjUwMyAwLDEuOTgxOTAxMjMgTDAsMTguMDE4MzY3NSBDMCwxOC41NjAzMDA2IDAuNDM5NDMwNjkxLDE5IDAuOTgxOTAxMjI5LDE5IEwyLjk0NTQzNSwxOSBDMy40ODc3NzEyLDE5IDMuOTI3MzM2MjMsMTguNTYwNTY5MyAzLjkyNzMzNjIzLDE4LjAxODM2NzUgQzMuOTI3MzM2MjMsMTcuNDc2MDMxMyAzLjQ4NzkwNTU0LDE3LjAzNjMzMTkgMi45NDU0MzUsMTcuMDM2MzMxOSBMMS45NjM2NjgxMiwxNy4wMzYzMzE5IEwxLjk2MzY2ODEyLDIuOTYzNjY4MTIgTDIuOTQ1NDM1LDIuOTYzNjY4MTIgWiIgaWQ9IlNoYXBlIiBmaWxsLXJ1bGU9Im5vbnplcm8iPjwvcGF0aD4KICAgICAgICAgICAgICAgIDwvZz4KICAgICAgICAgICAgPC9nPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+',
    title: 'Annotation'
  }
};
/* harmony default export */ __webpack_exports__["a"] = (annotations);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/boundaries.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var boundaries = {
  'bpmn:boundaryEvent:signalEventDefinition': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xMC43OTkgMS4yNUwxOC4xNjAzIDE0QzE4LjczNzYgMTUgMTguMDE1OSAxNi4yNSAxNi44NjEyIDE2LjI1SDIuMTM4NzhDMC45ODQwODQgMTYuMjUgMC4yNjIzOTYgMTUgMC44Mzk3NDYgMTRMOC4yMDA5NiAxLjI1QzguNzc4MzEgMC4yNSAxMC4yMjE3IDAuMjUgMTAuNzk5IDEuMjVaIgogICAgICAgICAgc3Ryb2tlPSIjNzg4NzkzIi8+Cjwvc3ZnPgo=',
    title: 'Boundary Signal Event'
  }
};
/* harmony default export */ __webpack_exports__["a"] = (boundaries);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/endEvents.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var endEventColor = '#ED4757';
var endEvents = {
  'bpmn:endEvent': {
    type: 'icon',
    class: 'far fa-circle',
    style: 'color:' + endEventColor + ';',
    title: 'End Event'
  },
  'bpmn:endEvent:messageEventDefinition': {
    type: 'icon',
    class: 'far fa-envelope',
    style: 'color:' + endEventColor + ';',
    title: 'Message End Event'
  },
  'bpmn:endEvent:errorEventDefinition': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTEiIGhlaWdodD0iMTMiIHZpZXdCb3g9IjAgMCAxMSAxMyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTcuNzM4NDQgNy4wMjgyOEM4LjgyNTY1IDQuNjg1MTIgOS45MTI3OSAyLjM0MzEgMTEgMEMxMC4xMjkzIDQuMTgwNjIgOS4yNTk1MSA4LjM1OTIxIDguMzg4NzcgMTIuNTM4OEM2Ljk2ODk2IDEwLjgxMjEgNS41NDkxNSA5LjA4NDI3IDQuMTI5MzQgNy4zNTc1OUMyLjc1MzI0IDkuMjM4MzcgMS4zNzYxIDExLjExOTIgMCAxM0MxLjIyODE4IDkuMTQwMjMgMi40NTUzNyA1LjI4MDQ1IDMuNjgzNTUgMS40MjA2MUM1LjAzNDgyIDMuMjg5ODQgNi4zODcxMiA1LjE1OTA2IDcuNzM4NDQgNy4wMjgyOFoiIGZpbGw9IiNFRDQ3NTciLz4KPC9zdmc+Cg==',
    title: 'Error End Event'
  },
  'bpmn:endEvent:signalEventDefinition': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEwLjc5OSAxLjI1TDE4LjE2MDMgMTRDMTguNzM3NiAxNSAxOC4wMTU5IDE2LjI1IDE2Ljg2MTIgMTYuMjVIMi4xMzg3OEMwLjk4NDA4NCAxNi4yNSAwLjI2MjM5NiAxNSAwLjgzOTc0NiAxNEw4LjIwMDk2IDEuMjVDOC43NzgzMSAwLjI1IDEwLjIyMTcgMC4yNSAxMC43OTkgMS4yNVoiCiAgICAgIGZpbGw9IiNFRDQ3NTciLz4KPC9zdmc+Cg==',
    title: 'Signal End Event'
  }
};
/* harmony default export */ __webpack_exports__["a"] = (endEvents);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/flows.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var flows = {
  'bpmn:sequenceFlow': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTlweCIgaGVpZ2h0PSIyMHB4IiB2aWV3Qm94PSIwIDAgMTkgMjAiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDx0aXRsZT5jb25uZWN0LWVsZW1lbnRzPC90aXRsZT4KICAgIDxkZXNjPkNyZWF0ZWQgd2l0aCBTa2V0Y2guPC9kZXNjPgogICAgPGcgaWQ9IlN5bWJvbHMiIHN0cm9rZT0ibm9uZSIgc3Ryb2tlLXdpZHRoPSIxIiBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPgogICAgICAgIDxnIGlkPSJjdXN0b20vY29ubmVjdC1lbGVtZW50cyIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTMuMDAwMDAwLCAtMi4wMDAwMDApIiBmaWxsPSIjMDAwIj4KICAgICAgICAgICAgPHBhdGggZmlsbD0iIzAwMCIKICAgICAgICAgICAgICAgICAgZD0iTTE4LjUsMjAgTDEzLDIwIEwxMyw1IEw4LDUgTDgsNyBMMyw3IEwzLDIgTDgsMiBMOCw0IEwxNCw0IEwxNCwxOSBMMTguNSwxOSBMMTguNSwxNyBMMjEuNSwxOS41IEwxOC41LDIyIEwxOC41LDIwIFogTTQsMyBMNCw2IEw3LDYgTDcsMyBMNCwzIFoiCiAgICAgICAgICAgICAgICAgIGlkPSJjb25uZWN0LWVsZW1lbnRzIi8+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4K',
    title: 'Sequence Flow'
  },
  'bpmn:messageFlow': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAxOSAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxnIGNsaXAtcGF0aD0idXJsKCNjbGlwMCkiPgogICAgICAgIDxjaXJjbGUgY3g9IjIuNSIgY3k9IjIuNSIgcj0iMiIgc3Ryb2tlPSIjMDAwIi8+CiAgICAgICAgPHBhdGggZD0iTTE1LjUgMjBMMTUuNSAxNy41VjE1TDE4LjUgMTcuNUwxNS41IDIwWiIgZmlsbD0iIzAwMCIvPgogICAgICAgIDxsaW5lIHgxPSI0IiB5MT0iMi41IiB4Mj0iNyIgeTI9IjIuNSIgc3Ryb2tlPSIjMDAwIi8+CiAgICAgICAgPGxpbmUgeDE9IjgiIHkxPSIyLjUiIHgyPSIxMSIgeTI9IjIuNSIgc3Ryb2tlPSIjMDAwIi8+CiAgICAgICAgPGxpbmUgeDE9IjEwLjUiIHkxPSI1IiB4Mj0iMTAuNSIgeTI9IjIiIHN0cm9rZT0iIzAwMCIvPgogICAgICAgIDxsaW5lIHgxPSIxMC41IiB5MT0iOSIgeDI9IjEwLjUiIHkyPSI2IiBzdHJva2U9IiMwMDAiLz4KICAgICAgICA8bGluZSB4MT0iMTAuNSIgeTE9IjEzIiB4Mj0iMTAuNSIgeTI9IjEwIiBzdHJva2U9IiMwMDAiLz4KICAgICAgICA8bGluZSB4MT0iMTAuNSIgeTE9IjE3IiB4Mj0iMTAuNSIgeTI9IjE0IiBzdHJva2U9IiMwMDAiLz4KICAgICAgICA8bGluZSB4MT0iMTAiIHkxPSIxNy41IiB4Mj0iMTMiIHkyPSIxNy41IiBzdHJva2U9IiMwMDAiLz4KICAgICAgICA8bGluZSB4MT0iMTQiIHkxPSIxNy41IiB4Mj0iMTciIHkyPSIxNy41IiBzdHJva2U9IiMwMDAiLz4KICAgIDwvZz4KICAgIDxkZWZzPgogICAgICAgIDxjbGlwUGF0aCBpZD0iY2xpcDAiPgogICAgICAgICAgICA8cmVjdCB3aWR0aD0iMTkiIGhlaWdodD0iMjAiIGZpbGw9ImJsYWNrIi8+CiAgICAgICAgPC9jbGlwUGF0aD4KICAgIDwvZGVmcz4KPC9zdmc+Cg==',
    title: 'Message Flow'
  }
};
/* harmony default export */ __webpack_exports__["a"] = (flows);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/gateways.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var gatewayColor = '#000';
var gateways = {
  'bpmn:exclusiveGateway': {
    type: 'icon',
    class: 'fa fa-times',
    style: 'color:' + gatewayColor + ';',
    title: 'Exclusive Gateway'
  },
  'bpmn:inclusiveGateway': {
    type: 'icon',
    class: 'far fa-circle',
    style: 'color:' + gatewayColor + ';',
    title: 'Inclusive Gateway'
  },
  'bpmn:parallelGateway': {
    type: 'icon',
    class: 'fa fa-plus',
    style: 'color:' + gatewayColor + ';',
    title: 'Parallel Gateway'
  },
  'bpmn:eventBasedGateway': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTU0cHgiIGhlaWdodD0iMTU0cHgiIHZpZXdCb3g9IjAgMCAxNTQgMTU0IiB2ZXJzaW9uPSIxLjEiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiPgogICAgPCEtLSBHZW5lcmF0b3I6IFNrZXRjaCA1MC4yICg1NTA0NykgLSBodHRwOi8vd3d3LmJvaGVtaWFuY29kaW5nLmNvbS9za2V0Y2ggLS0+CiAgICA8dGl0bGU+ZXZlbnQtYmFzZWQtZ2F0ZXdheS1zeW1ib2w8L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZGVmcz48L2RlZnM+CiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8ZyBpZD0iZXZlbnQtYmFzZWQtZ2F0ZXdheS1zeW1ib2wiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDQuMDAwMDAwLCA0LjAwMDAwMCkiIHN0cm9rZT0iIzAwMDAwMCI+CiAgICAgICAgICAgIDxjaXJjbGUgaWQ9Ik92YWwiIHN0cm9rZS13aWR0aD0iOCIgY3g9IjczIiBjeT0iNzMiIHI9IjczIj48L2NpcmNsZT4KICAgICAgICAgICAgPGNpcmNsZSBpZD0iT3ZhbC1Db3B5IiBzdHJva2Utd2lkdGg9IjgiIGN4PSI3My41IiBjeT0iNzMuNSIgcj0iNTUuNSI+PC9jaXJjbGU+CiAgICAgICAgICAgIDxwb2x5Z29uIGlkPSJQb2x5Z29uIiBzdHJva2Utd2lkdGg9IjciIHBvaW50cz0iNzMuMDcyNTgwNiA0MSAxMDcuMTQ1MTYxIDY1LjgyNzc5MDcgOTQuMTMwNTkzNiAxMDYgNTIuMDE0NTY3NyAxMDYgMzkgNjUuODI3NzkwNyI+PC9wb2x5Z29uPgogICAgICAgIDwvZz4KICAgIDwvZz4KPC9zdmc+',
    title: 'Event Based Gateway'
  }
};
/* harmony default export */ __webpack_exports__["a"] = (gateways);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/index.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__annotations__ = __webpack_require__("./resources/js/processes/modeler/print/icons/annotations.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__boundaries__ = __webpack_require__("./resources/js/processes/modeler/print/icons/boundaries.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__endEvents__ = __webpack_require__("./resources/js/processes/modeler/print/icons/endEvents.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_3__flows__ = __webpack_require__("./resources/js/processes/modeler/print/icons/flows.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_4__gateways__ = __webpack_require__("./resources/js/processes/modeler/print/icons/gateways.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_5__intermediateEvents__ = __webpack_require__("./resources/js/processes/modeler/print/icons/intermediateEvents.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_6__pools__ = __webpack_require__("./resources/js/processes/modeler/print/icons/pools.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_7__startEvents__ = __webpack_require__("./resources/js/processes/modeler/print/icons/startEvents.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_8__tasks__ = __webpack_require__("./resources/js/processes/modeler/print/icons/tasks.js");
var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };











var icons = _extends({}, __WEBPACK_IMPORTED_MODULE_0__annotations__["a" /* default */], __WEBPACK_IMPORTED_MODULE_1__boundaries__["a" /* default */], __WEBPACK_IMPORTED_MODULE_2__endEvents__["a" /* default */], __WEBPACK_IMPORTED_MODULE_3__flows__["a" /* default */], __WEBPACK_IMPORTED_MODULE_4__gateways__["a" /* default */], __WEBPACK_IMPORTED_MODULE_5__intermediateEvents__["a" /* default */], __WEBPACK_IMPORTED_MODULE_6__pools__["a" /* default */], __WEBPACK_IMPORTED_MODULE_7__startEvents__["a" /* default */], __WEBPACK_IMPORTED_MODULE_8__tasks__["a" /* default */]);

/* harmony default export */ __webpack_exports__["a"] = (icons);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/intermediateEvents.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var intermediateEventColor = '#FBBE02';
var intermediateEvents = {
  'bpmn:intermediateCatchEvent:timerEventDefinition': {
    type: 'icon',
    class: 'far fa-clock',
    style: 'color:' + intermediateEventColor + ';',
    title: 'Intermediate Timer Event'
  },
  'bpmn:intermediateThrowEvent:messageEventDefinition': {
    type: 'icon',
    class: 'fa fa-envelope',
    style: 'color:' + intermediateEventColor + ';',
    title: 'Intermediate Message Throw Event'
  },
  'bpmn:intermediateCatchEvent:messageEventDefinition': {
    type: 'icon',
    class: 'far fa-envelope',
    style: 'color:' + intermediateEventColor + ';',
    title: 'Intermediate Message Catch Event'
  },
  'bpmn:intermediateCatchEvent:signalEventDefinition': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xMC43OTkgMS4yNUwxOC4xNjAzIDE0QzE4LjczNzYgMTUgMTguMDE1OSAxNi4yNSAxNi44NjEyIDE2LjI1SDIuMTM4NzhDMC45ODQwODQgMTYuMjUgMC4yNjIzOTYgMTUgMC44Mzk3NDYgMTRMOC4yMDA5NiAxLjI1QzguNzc4MzEgMC4yNSAxMC4yMjE3IDAuMjUgMTAuNzk5IDEuMjVaIgogICAgICAgICAgc3Ryb2tlPSIjRkJCRTAyIi8+Cjwvc3ZnPgo=',
    title: 'Intermediate Signal Catch Event'
  },
  'bpmn:intermediateThrowEvent:signalEventDefinition': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xMC43OTkgMS4yNUwxOC4xNjAzIDE0QzE4LjczNzYgMTUgMTguMDE1OSAxNi4yNSAxNi44NjEyIDE2LjI1SDIuMTM4NzhDMC45ODQwODQgMTYuMjUgMC4yNjIzOTYgMTUgMC44Mzk3NDYgMTRMOC4yMDA5NiAxLjI1QzguNzc4MzEgMC4yNSAxMC4yMjE3IDAuMjUgMTAuNzk5IDEuMjVaIgogICAgICAgICAgZmlsbD0iI0ZBQkQyRCIvPgo8L3N2Zz4K',
    title: 'Intermediate Signal Throw Event'
  }
};

/* harmony default export */ __webpack_exports__["a"] = (intermediateEvents);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/pools.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var pools = {
  'bpmn:participant': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMjdweCIgaGVpZ2h0PSIxOHB4IiB2aWV3Qm94PSIwIDAgMjcgMTgiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8IS0tIEdlbmVyYXRvcjogU2tldGNoIDUwLjIgKDU1MDQ3KSAtIGh0dHA6Ly93d3cuYm9oZW1pYW5jb2RpbmcuY29tL3NrZXRjaCAtLT4KICAgIDx0aXRsZT5jdXN0b20vcHJvY2Vzc2J1aWxkZXIvcG9vbDI8L3RpdGxlPgogICAgPGRlc2M+Q3JlYXRlZCB3aXRoIFNrZXRjaC48L2Rlc2M+CiAgICA8ZGVmcz48L2RlZnM+CiAgICA8ZyBpZD0iY3VzdG9tL3Byb2Nlc3NidWlsZGVyL3Bvb2wyIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8cmVjdCBpZD0iUmVjdGFuZ2xlIiBzdHJva2U9IiMzMzMzMzMiIGZpbGw9IiNGRkZGRkYiIHg9IjAuNSIgeT0iMC41IiB3aWR0aD0iMjYiIGhlaWdodD0iMTciPjwvcmVjdD4KICAgICAgICA8cmVjdCBpZD0iUmVjdGFuZ2xlLTMiIGZpbGw9IiMzMzMzMzMiIHg9IjciIHk9IjEiIHdpZHRoPSIxIiBoZWlnaHQ9IjE2Ij48L3JlY3Q+CiAgICA8L2c+Cjwvc3ZnPg==',
    title: 'Pool'
  }
};
/* harmony default export */ __webpack_exports__["a"] = (pools);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/startEvents.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var startEventColor = '#00BF9C';
var startEvents = {
  'bpmn:startEvent': {
    type: 'icon',
    class: 'far fa-circle',
    style: 'color:' + startEventColor + ';',
    title: 'Start Event'
  },
  'bpmn:startEvent:timerEventDefinition': {
    type: 'icon',
    class: 'far fa-clock',
    style: 'color:' + startEventColor + ';',
    title: 'Start Timer Event'
  },
  'bpmn:startEvent:messageEventDefinition': {
    type: 'icon',
    class: 'far fa-envelope',
    style: 'color:' + startEventColor + ';',
    title: 'Message Start Event'
  },
  'bpmn:startEvent:signalEventDefinition': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTkiIGhlaWdodD0iMTciIHZpZXdCb3g9IjAgMCAxOSAxNyIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICAgIDxwYXRoIGQ9Ik0xMC43OTkgMS4yNUwxOC4xNjAzIDE0QzE4LjczNzYgMTUgMTguMDE1OSAxNi4yNSAxNi44NjEyIDE2LjI1SDIuMTM4NzhDMC45ODQwODQgMTYuMjUgMC4yNjIzOTYgMTUgMC44Mzk3NDYgMTRMOC4yMDA5NiAxLjI1QzguNzc4MzEgMC4yNSAxMC4yMjE3IDAuMjUgMTAuNzk5IDEuMjVaIgogICAgICAgICAgc3Ryb2tlPSIjMDBCRjlDIi8+Cjwvc3ZnPgo=',
    title: 'Signal Start Event'
  }
};

/* harmony default export */ __webpack_exports__["a"] = (startEvents);

/***/ }),

/***/ "./resources/js/processes/modeler/print/icons/tasks.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var taskColor = '#788793';
var tasks = {
  'bpmn:task': {
    type: 'icon',
    class: 'fa fa-user',
    style: 'color:' + taskColor + ';',
    title: 'Task'
  },
  'bpmn:manualTask': {
    type: 'icon',
    class: 'far fa-hand-paper',
    style: 'color:' + taskColor + ';',
    title: 'Manual Task'
  },
  'bpmn:scriptTask': {
    type: 'image',
    src: 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4KPHN2ZyB3aWR0aD0iMTRweCIgaGVpZ2h0PSIxNHB4IiB2aWV3Qm94PSIwIDAgMTQgMTQiIHZlcnNpb249IjEuMSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayI+CiAgICA8ZyBpZD0iUGFnZS0xIiBzdHJva2U9Im5vbmUiIHN0cm9rZS13aWR0aD0iMSIgZmlsbD0ibm9uZSIgZmlsbC1ydWxlPSJldmVub2RkIj4KICAgICAgICA8ZyBpZD0iR3JvdXAiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDEuMDAwMDAwLCAwLjAwMDAwMCkiIHN0cm9rZT0iIzc4ODc5MyI+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik0xMi4wMDAwMTMzLDEgQzEwLjgzNDg1MjgsMS40MDY2ODA2IDEwLjAzMzE4NzgsMi40ODYzNTk1NiA5LjU5NTAxODQ2LDQuMjM5MDM2ODkgQzkuMzA0NDY0MzIsNS40MDEyNTM0NyAxMC41Nzk2NDM5LDcuNDk5MTkzNTEgMTAuNTAwMDA2Nyw5LjY4ODI2ODQ0IEMxMC40NzMwMzM3LDEwLjQyOTcwMzkgOS45NzMwMzE0OCwxMS41MzM2MTQ0IDksMTMiIGlkPSJQYXRoLTMiPjwvcGF0aD4KICAgICAgICAgICAgPHBhdGggZD0iTTMuMDAwMDEzMzQsMSBDMS44MzQ4NTI3NSwxLjQwNjY4MDYgMS4wMzMxODc3OSwyLjQ4NjM1OTU2IDAuNTk1MDE4NDYyLDQuMjM5MDM2ODkgQzAuMzA0NDY0MzE3LDUuNDAxMjUzNDcgMS41Nzk2NDM4Nyw3LjQ5OTE5MzUxIDEuNTAwMDA2NjcsOS42ODgyNjg0NCBDMS40NzMwMzM3LDEwLjQyOTcwMzkgMC45NzMwMzE0ODEsMTEuNTMzNjE0NCAtOC44ODE3ODQyZS0xNiwxMyIgaWQ9IlBhdGgtMyI+PC9wYXRoPgogICAgICAgICAgICA8cGF0aCBkPSJNMy4wMDAwMTMzNCwxIEwxMi4wMDAwMTMzLDEiIGlkPSJQYXRoLTQiPjwvcGF0aD4KICAgICAgICAgICAgPHBhdGggZD0iTTAsMTMgTDksMTMiIGlkPSJQYXRoLTUiPjwvcGF0aD4KICAgICAgICAgICAgPHBhdGggZD0iTTIsNC41IEw3LjE4NTg5ODU1LDQuNSIgaWQ9IlBhdGgtNiI+PC9wYXRoPgogICAgICAgICAgICA8cGF0aCBkPSJNMyw2LjUgTDguMTg1ODk4NTUsNi41IiBpZD0iUGF0aC02Ij48L3BhdGg+CiAgICAgICAgICAgIDxwYXRoIGQ9Ik00LDguNSBMOS4xODU4OTg1NSw4LjUiIGlkPSJQYXRoLTYiPjwvcGF0aD4KICAgICAgICAgICAgPHBhdGggZD0iTTMsMTAuNSBMOC4xODU4OTg1NSwxMC41IiBpZD0iUGF0aC02Ij48L3BhdGg+CiAgICAgICAgPC9nPgogICAgPC9nPgo8L3N2Zz4K',
    title: 'Script Task'
  },
  'bpmn:callActivity': {
    type: 'icon',
    class: 'far fa-square',
    style: 'color:' + taskColor + ';',
    title: 'Sub Process'
  }
};

/* harmony default export */ __webpack_exports__["a"] = (tasks);

/***/ }),

/***/ "./resources/js/processes/modeler/print/index.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
Object.defineProperty(__webpack_exports__, "__esModule", { value: true });
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue__ = __webpack_require__("./node_modules/vue/dist/vue.common.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0_vue___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_0_vue__);
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__PrintableDiagram__ = __webpack_require__("./resources/js/processes/modeler/print/PrintableDiagram.vue");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__PrintableDiagram___default = __webpack_require__.n(__WEBPACK_IMPORTED_MODULE_1__PrintableDiagram__);



new __WEBPACK_IMPORTED_MODULE_0_vue___default.a({
  el: '#printable-view',
  components: { PrintableDiagram: __WEBPACK_IMPORTED_MODULE_1__PrintableDiagram___default.a },
  template: '\n      <div id="printable-view">\n          <PrintableDiagram\n                  :processName="processName"\n                  :updatedAt="updatedAt"\n                  :author="author"\n                  :svg="svg"\n                  :bpmn="bpmn"\n          />\n      </div>',
  data: {
    processName: window.ProcessMaker.modeler.processName,
    updatedAt: window.ProcessMaker.modeler.updatedAt,
    author: window.ProcessMaker.modeler.author,
    svg: window.ProcessMaker.modeler.svg,
    bpmn: window.ProcessMaker.modeler.bpmn
  }
});

/***/ }),

/***/ "./resources/js/processes/modeler/print/parseBpmnDocumentation/documentationParser.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return nodeDocumentation; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return nodeText; });
/**
 * Extract the .textContent of the children of bpmnNode
 * that has tagName == childType. Returns '' if no such child exists.
 */
var extractTextContentOfChildren = function extractTextContentOfChildren(bpmnNode, childType) {
  var textContent = '';
  bpmnNode.childNodes.forEach(function (childNode) {
    if (childNode.tagName === childType) {
      textContent += childNode.textContent;
    }
  });
  return textContent;
};

var isTextAnnotationElement = function isTextAnnotationElement(node) {
  return node.tagName === 'bpmn:textAnnotation';
};

/**
 * Return the documentation of a node, if it exists.
 */
var nodeDocumentation = function nodeDocumentation(bpmnNode) {
  return extractTextContentOfChildren(bpmnNode, 'bpmn:documentation');
};

/**
 * Return the text child's contents of an annotation element,
 * if it exists.
 */
var nodeText = function nodeText(bpmnNode) {
  if (isTextAnnotationElement(bpmnNode)) {
    return extractTextContentOfChildren(bpmnNode, 'bpmn:text');
  }
  return '';
};



/***/ }),

/***/ "./resources/js/processes/modeler/print/parseBpmnDocumentation/index.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_0__typeParser__ = __webpack_require__("./resources/js/processes/modeler/print/parseBpmnDocumentation/typeParser.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_1__nameParser__ = __webpack_require__("./resources/js/processes/modeler/print/parseBpmnDocumentation/nameParser.js");
/* harmony import */ var __WEBPACK_IMPORTED_MODULE_2__documentationParser__ = __webpack_require__("./resources/js/processes/modeler/print/parseBpmnDocumentation/documentationParser.js");




var allowedName = function allowedName(bpmnNode) {
  var nodesToIncludeAnyway = ['bpmn:textAnnotation', 'bpmn:messageFlow', 'bpmn:sequenceFlow'];
  return Object(__WEBPACK_IMPORTED_MODULE_1__nameParser__["b" /* hasNonEmptyName */])(bpmnNode) || nodesToIncludeAnyway.includes(bpmnNode.tagName);
};

var editorSpecificNodes = function editorSpecificNodes(bpmnNode) {
  var adonis = 'adonis:';

  var appSpecificNamespaces = [adonis];

  return !appSpecificNamespaces.some(function (appNamespace) {
    return bpmnNode.tagName.includes(appNamespace);
  });
};

var undocumentableNodes = function undocumentableNodes(bpmnNode) {
  var nodesThatCannotBeDocumented = ['bpmn:process', 'bpmn:error', 'bpmn:message', 'bpmn:signal', 'signal'];
  return !nodesThatCannotBeDocumented.includes(bpmnNode.tagName);
};

function documentableBpmnNodes(bpmnString) {
  var bpmnDoc = new DOMParser().parseFromString(bpmnString, 'text/xml');
  return Array.from(bpmnDoc.querySelectorAll('*[id]:not([id=""])')).filter(allowedName).filter(editorSpecificNodes).filter(undocumentableNodes).map(function (bpmnNode) {
    return {
      id: bpmnNode.attributes.getNamedItem('id').textContent,
      type: Object(__WEBPACK_IMPORTED_MODULE_0__typeParser__["a" /* default */])(bpmnNode),
      name: Object(__WEBPACK_IMPORTED_MODULE_1__nameParser__["a" /* getNodeName */])(bpmnNode),
      documentationHtml: Object(__WEBPACK_IMPORTED_MODULE_2__documentationParser__["a" /* nodeDocumentation */])(bpmnNode),
      textHtml: Object(__WEBPACK_IMPORTED_MODULE_2__documentationParser__["b" /* nodeText */])(bpmnNode)
    };
  });
}

/* harmony default export */ __webpack_exports__["a"] = (documentableBpmnNodes);

/***/ }),

/***/ "./resources/js/processes/modeler/print/parseBpmnDocumentation/nameParser.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "a", function() { return getNodeName; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "b", function() { return hasNonEmptyName; });
var getNodeName = function getNodeName(bpmnNode) {
  var name = bpmnNode.attributes.getNamedItem('name');
  if (!name || !name.textContent) {
    switch (bpmnNode.tagName) {
      case 'bpmn:sequenceFlow':
        return 'Unnamed Sequence Flow';
      case 'bpmn:messageFlow':
        return 'Unnamed Message Flow';
      case 'bpmn:textAnnotation':
        return 'Annotation';
      default:
        return '';
    }
  }
  return name.textContent;
};

var hasNonEmptyName = function hasNonEmptyName(node) {
  return getNodeName(node) !== '';
};



/***/ }),

/***/ "./resources/js/processes/modeler/print/parseBpmnDocumentation/typeParser.js":
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
var hasChildNode = function hasChildNode(node, childType) {
  var found = false;
  node.childNodes.forEach(function (childNode) {
    if (childNode.tagName && childNode.tagName.includes(childType)) {
      found = true;
    }
  });
  return found;
};

var hasSubType = function hasSubType(subType) {
  return function (parentNode) {
    return hasChildNode(parentNode, subType);
  };
};
var hasTimerEventDefinition = hasSubType('timerEventDefinition');
var hasMessageEventDefinition = hasSubType('messageEventDefinition');
var hasErrorEventDefinition = hasSubType('errorEventDefinition');
var hasSignalEventDefinition = hasSubType('signalEventDefinition');

var prependBpmnNamespace = function prependBpmnNamespace(nodeTagName) {
  if (nodeTagName.startsWith('bpmn')) {
    return nodeTagName;
  }
  return 'bpmn:' + nodeTagName;
};

var getFullyQualifiedNodeType = function getFullyQualifiedNodeType(node) {
  var tagName = prependBpmnNamespace(node.tagName);
  if (hasTimerEventDefinition(node)) {
    return tagName + ':timerEventDefinition';
  }
  if (hasMessageEventDefinition(node)) {
    return tagName + ':messageEventDefinition';
  }
  if (hasErrorEventDefinition(node)) {
    return tagName + ':errorEventDefinition';
  }
  if (hasSignalEventDefinition(node)) {
    return tagName + ':signalEventDefinition';
  }
  return tagName;
};

/* harmony default export */ __webpack_exports__["a"] = (getFullyQualifiedNodeType);

/***/ }),

/***/ 3:
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__("./resources/js/processes/modeler/print/index.js");


/***/ })

},[3]);