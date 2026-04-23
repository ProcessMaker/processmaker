import {
  getGlobalVariable, setGlobalVariable, getGlobalPMVariable, setGlobalPMVariable,
} from "./globalVariables";
import attachScreenCacheAdapter from "../common/attachScreenCacheAdapter";

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

        // Configuration Global object used by ScreenBuilder
        // @link https://processmaker.atlassian.net/browse/FOUR-6833 Cache configuration
        const screenCacheEnabled = document.head.querySelector("meta[name=\"screen-cache-enabled\"]")?.content ?? "false";
        const screenCacheTimeout = document.head.querySelector("meta[name=\"screen-cache-timeout\"]")?.content ?? "5000";
        const screenSecureHandlerToggleVisible = document.head.querySelector("meta[name='screen-secure-handler-toggle-visible']");
        const screenMergeDraftOnRestore = document.head.querySelector("meta[name='screen-merge-draft-on-restore']")?.content ?? "true";
        const screen = {
          cacheEnabled: screenCacheEnabled === "true",
          cacheTimeout: Number(screenCacheTimeout),
          secureHandlerToggleVisible: !!Number(screenSecureHandlerToggleVisible?.content),
          mergeDraftOnRestore: screenMergeDraftOnRestore === "true",
        };

        setGlobalVariable("ScreenBuilder", ScreenBuilder);
        setGlobalPMVariable("screen", screen);
        attachScreenCacheAdapter(apiClient, screen);
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
