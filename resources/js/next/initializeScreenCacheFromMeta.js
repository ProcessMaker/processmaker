import { initializeScreenCache } from "@processmaker/screen-builder";
import { getGlobalPMVariable, setGlobalPMVariable } from "./globalVariables";

let cacheInitialized = false;

function getScreenConfigFromMeta() {
  const screenCacheEnabled = document.head.querySelector('meta[name="screen-cache-enabled"]')?.content ?? "false";
  const screenCacheTimeout = document.head.querySelector('meta[name="screen-cache-timeout"]')?.content ?? "5000";
  const screenSecureHandlerToggleVisible = document.head.querySelector("meta[name='screen-secure-handler-toggle-visible']");
  const screenMergeDraftOnRestore = document.head.querySelector("meta[name='screen-merge-draft-on-restore']")?.content ?? "true";

  return {
    cacheEnabled: screenCacheEnabled === "true",
    cacheTimeout: Number(screenCacheTimeout),
    secureHandlerToggleVisible: !!Number(screenSecureHandlerToggleVisible?.content),
    mergeDraftOnRestore: screenMergeDraftOnRestore === "true",
  };
}

export default function initializeScreenCacheFromMeta(apiClient = getGlobalPMVariable("apiClient")) {
  if (cacheInitialized) {
    return getGlobalPMVariable("screen");
  }

  const screen = getScreenConfigFromMeta();
  setGlobalPMVariable("screen", screen);
  initializeScreenCache(apiClient, screen);
  cacheInitialized = true;

  return screen;
}
