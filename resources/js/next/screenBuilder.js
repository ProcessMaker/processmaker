import {
  getGlobalVariable, setGlobalVariable, getGlobalPMVariable, setGlobalPMVariable,
} from "./globalVariables";

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

        const { initializeScreenCache } = ScreenBuilder;
        // Configuration Global object used by ScreenBuilder
        // @link https://processmaker.atlassian.net/browse/FOUR-6833 Cache configuration
        const screenCacheEnabled = document.head.querySelector("meta[name=\"screen-cache-enabled\"]")?.content ?? "false";
        const screenCacheTimeout = document.head.querySelector("meta[name=\"screen-cache-timeout\"]")?.content ?? "5000";
        const screen = {
          cacheEnabled: screenCacheEnabled === "true",
          cacheTimeout: Number(screenCacheTimeout),
        };

        setGlobalVariable("ScreenBuilder", ScreenBuilder);
        setGlobalPMVariable("screen", screen);
        // Initialize screen-builder cache
        initializeScreenCache(apiClient, screen);// TODO: Its a bad practice to use the apiClient here
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
