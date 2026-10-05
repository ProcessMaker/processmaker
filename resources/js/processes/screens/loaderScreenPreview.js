import * as ScreenBuilder from "@processmaker/screen-builder";
import VueFormElements from "@processmaker/vue-form-elements";
import { setupMain } from "../../next/setupMain";

const { initializeScreenCache } = ScreenBuilder;

setupMain();

// Screen preview uses Vite (loaderScreenPreview) instead of bootstrap.js.
// DataProvider.getDataSource() requires ProcessMaker.screen to be initialized.
// @link https://processmaker.atlassian.net/browse/FOUR-6833 Cache configuration
const screenCacheEnabled = document.head.querySelector('meta[name="screen-cache-enabled"]')?.content ?? "false";
const screenCacheTimeout = document.head.querySelector('meta[name="screen-cache-timeout"]')?.content ?? "5000";
const screenSecureHandlerToggleVisible = document.head.querySelector("meta[name='screen-secure-handler-toggle-visible']");
window.ProcessMaker.screen = {
  cacheEnabled: screenCacheEnabled === "true",
  cacheTimeout: Number(screenCacheTimeout),
  secureHandlerToggleVisible: !!Number(screenSecureHandlerToggleVisible?.content),
};
initializeScreenCache(window.ProcessMaker.apiClient, window.ProcessMaker.screen);

window.ScreenBuilder = ScreenBuilder;
window.VueFormElements = VueFormElements;
window.Vue.use(ScreenBuilder.default);

window.ProcessMaker.packages = window.temporal?.packages || [];
window.packages = window.ProcessMaker.packages;
