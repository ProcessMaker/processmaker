import { getGlobalPMVariable, setGlobalPMVariables, getGlobalVariable } from "../globalVariables";
import { initSessionSync } from "../../common/sessionSync";

export default () => {
  const timeoutScript = document.head.querySelector("meta[name=\"timeout-worker\"]")?.content;

  const Vue = getGlobalVariable("Vue");
  const Echo = getGlobalVariable("Echo");

  const pushNotification = getGlobalPMVariable("pushNotification");
  const alert = getGlobalPMVariable("alert");
  const user = getGlobalPMVariable("user");
  const isProd = document.head.querySelector("meta[name=\"is-prod\"]")?.content === "true";

  if (!user) {
    return;
  }

  // Backend provides minutes for lifetime and seconds for warnings.
  const accountTimeoutLength = parseInt(eval(document.head.querySelector("meta[name=\"timeout-length\"]")?.content));
  const warnSeconds = parseInt(document.head.querySelector("meta[name=\"timeout-warn-seconds\"]")?.content);
  const accountTimeoutWarnSeconds = Number.isNaN(warnSeconds) ? 0 : warnSeconds;
  const accountTimeoutEnabled = document.head.querySelector("meta[name=\"timeout-enabled\"]") ? parseInt(document.head.querySelector("meta[name=\"timeout-enabled\"]")?.content) : 1;

  const sessionSyncState = initSessionSync({
    userId: user.id,
    isProd,
    timeoutScript,
    accountTimeoutLength,
    accountTimeoutWarnSeconds,
    accountTimeoutEnabled,
    Vue,
    Echo,
    pushNotification,
    alert,
    getSessionModal: () => getGlobalPMVariable("sessionModal"),
    getCloseSessionModal: () => getGlobalPMVariable("closeSessionModal"),
    getNavbar: () => getGlobalPMVariable("navbar"),
  });

  if (!sessionSyncState) {
    return;
  }

  setGlobalPMVariables({
    AccountTimeoutLength: sessionSyncState.AccountTimeoutLength,
    AccountTimeoutWarnSeconds: sessionSyncState.AccountTimeoutWarnSeconds,
    AccountTimeoutWarnMinutes: sessionSyncState.AccountTimeoutWarnMinutes,
    AccountTimeoutEnabled: sessionSyncState.AccountTimeoutEnabled,
    AccountTimeoutWorker: sessionSyncState.AccountTimeoutWorker,
    sessionSync: sessionSyncState.sessionSync,
  });
};
