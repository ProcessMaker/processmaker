import {
  getGlobalVariable, setGlobalVariable, getGlobalPMVariable,
} from "./globalVariables";
import initializeScreenCacheFromMeta from "./initializeScreenCacheFromMeta";

const addScriptsToDOM = async function (scripts) {
  for (const script of scripts) {
    await new Promise((resolve, reject) => {
      const scriptElement = document.createElement("script");
      scriptElement.src = script;
      scriptElement.async = false;
      scriptElement.onload = resolve;
      scriptElement.onerror = reject;
      document.head.appendChild(scriptElement);
    });
  }
};

export default () => {
  const Vue = getGlobalVariable("Vue");

  const componentsScreenBuilder = ["VueFormRenderer", "Task"];

  componentsScreenBuilder.forEach((component) => {
    Vue.component(component, (resolve, reject) => {
      import("@processmaker/screen-builder/dist/vue-form-builder.css");
      import("@processmaker/screen-builder").then((ScreenBuilder) => {
        const apiClient = getGlobalPMVariable("apiClient");

        setGlobalVariable("ScreenBuilder", ScreenBuilder);
        initializeScreenCacheFromMeta(apiClient);
        if (screenBuilderScripts) {
          addScriptsToDOM(screenBuilderScripts).then(() => {
            // The order of the scripts is important, the screenBuilderScripts must be loaded before the ScreenBuilder.default
            Vue.use(ScreenBuilder.default);
            resolve(ScreenBuilder[component]);
          });
        }
      }).catch(reject);
    });
  });
};
