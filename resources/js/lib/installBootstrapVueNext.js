import * as BV from "bootstrap-vue-next";
import {
  componentNames,
  createBootstrap,
  directiveNames,
} from "bootstrap-vue-next";

const directiveVueNames = {
  vBColorMode: "b-color-mode",
  vBModal: "b-modal",
  vBPopover: "b-popover",
  vBScrollspy: "b-scrollspy",
  vBToggle: "b-toggle",
  vBTooltip: "b-tooltip",
};

/**
 * bootstrap-vue-next has no default export; register components and directives globally.
 */
export default {
  install(app) {
    app.use(createBootstrap());
    componentNames.forEach((name) => {
      const comp = BV[name];
      if (comp) {
        app.component(name, comp);
      }
    });
    directiveNames.forEach((d) => {
      const impl = BV[d];
      if (impl) {
        app.directive(directiveVueNames[d] || d, impl);
      }
    });
  },
};
