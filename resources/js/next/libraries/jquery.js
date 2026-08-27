import $ from "jquery";

// Sortable (vue-form-builder) does: (window.jQuery || window.Zepto)(el).clone(true)[0]
// It must be the jQuery function, not `import * as jQuery` (namespace is truthy but not callable).
window.$ = $;
window.jQuery = $;

export default {
  global: {
  },
};
