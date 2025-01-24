import { getGlobalPMVariable, setGlobalPMVariables, getGlobalVariable } from "../globalVariables";

export default () => {
  const timeoutScript = document.head.querySelector("meta[name=\"timeout-worker\"]")?.content;

  const Vue = getGlobalVariable("Vue");
  const Echo = getGlobalVariable("Echo");

  const pushNotification = getGlobalPMVariable("pushNotification");
  const closeSessionModal = getGlobalPMVariable("closeSessionModal");
  const alert = getGlobalPMVariable("alert");
  const user = getGlobalPMVariable("user");
  const sessionModal = getGlobalPMVariable("sessionModal");

  const isSameDevice = (e) => {
    const localDeviceId = Vue.$cookies.get(e.device_variable);
    const remoteDeviceId = e.device_id;
    return localDeviceId && localDeviceId === remoteDeviceId;
  };

  if (user) {
  // Session timeout
    const AccountTimeoutLength = parseInt(eval(document.head.querySelector("meta[name=\"timeout-length\"]")?.content));
    const AccountTimeoutWarnSeconds = parseInt(document.head.querySelector("meta[name=\"timeout-warn-seconds\"]")?.content);
    const AccountTimeoutEnabled = document.head.querySelector("meta[name=\"timeout-enabled\"]") ? parseInt(document.head.querySelector("meta[name=\"timeout-enabled\"]")?.content) : 1;
    const AccountTimeoutWorker = new Worker(timeoutScript);

    AccountTimeoutWorker.addEventListener("message", (e) => {
      if (e.data.method === "countdown") {
        sessionModal(
          "Session Warning",
          "<p>Your user session is expiring. If your session expires, all of your unsaved data will be lost.</p><p>Would you like to stay connected?</p>",
          e.data.data.time,
          AccountTimeoutWarnSeconds,
        );
      }
      if (e.data.method === "timedOut") {
        window.location = "/logout?timeout=true";
      }
    });

    // in some cases it's necessary to start manually
    AccountTimeoutWorker.postMessage({
      method: "start",
      data: {
        timeout: AccountTimeoutLength,
        warnSeconds: AccountTimeoutWarnSeconds,
        enabled: AccountTimeoutEnabled,
      },
    });

    Echo.private(`ProcessMaker.Models.User.${user.id}`)
      .notification((token) => {
        pushNotification(token);
      })
      .listen(".SessionStarted", (e) => {
        const lifetime = parseInt(eval(e.lifetime));
        if (isSameDevice(e)) {
          AccountTimeoutWorker.postMessage({
            method: "start",
            data: {
              timeout: lifetime,
              warnSeconds: AccountTimeoutWarnSeconds,
              enabled: AccountTimeoutEnabled,
            },
          });
          if (closeSessionModal) {
            closeSessionModal();
          }
        }
      })
      .listen(".Logout", (e) => {
        if (isSameDevice(e) && window.location.pathname.indexOf("/logout") === -1) {
          const localDeviceId = Vue.$cookies.get(e.device_variable);
          const redirectLogoutinterval = setInterval(() => {
            const newDeviceId = Vue.$cookies.get(e.device_variable);
            if (localDeviceId !== newDeviceId) {
              clearInterval(redirectLogoutinterval);
              window.location.href = "/logout";
            }
          }, 100);
        }
      })
      .listen(".SecurityLogDownloadJobCompleted", (e) => {
        if (e.success) {
          const { link } = e;
          const { message } = e;
          alert(message, "success", 0, false, false, link);
        } else {
          alert(e.message, "warning");
        }
      });

    setGlobalPMVariables({
      AccountTimeoutLength,
      AccountTimeoutWarnSeconds,
      AccountTimeoutEnabled,
      AccountTimeoutWorker,
    });
  }
};
