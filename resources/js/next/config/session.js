if (window.ProcessMaker.user) {
  // Session timeout
  const timeoutScript = document.head.querySelector("meta[name=\"timeout-worker\"]")?.content;
  window.ProcessMaker.AccountTimeoutLength = parseInt(eval(document.head.querySelector("meta[name=\"timeout-length\"]")?.content));
  window.ProcessMaker.AccountTimeoutWarnSeconds = parseInt(document.head.querySelector("meta[name=\"timeout-warn-seconds\"]")?.content);
  window.ProcessMaker.AccountTimeoutEnabled = document.head.querySelector("meta[name=\"timeout-enabled\"]") ? parseInt(document.head.querySelector("meta[name=\"timeout-enabled\"]")?.content) : 1;
  window.ProcessMaker.AccountTimeoutWorker = new Worker(timeoutScript);
  window.ProcessMaker.AccountTimeoutWorker.addEventListener("message", (e) => {
    if (e.data.method === "countdown") {
      window.ProcessMaker.sessionModal(
        "Session Warning",
        "<p>Your user session is expiring. If your session expires, all of your unsaved data will be lost.</p><p>Would you like to stay connected?</p>",
        e.data.data.time,
        window.ProcessMaker.AccountTimeoutWarnSeconds,
      );
    }
    if (e.data.method === "timedOut") {
      window.location = "/logout?timeout=true";
    }
  });

  // in some cases it's necessary to start manually
  window.ProcessMaker.AccountTimeoutWorker.postMessage({
    method: "start",
    data: {
      timeout: window.ProcessMaker.AccountTimeoutLength,
      warnSeconds: window.ProcessMaker.AccountTimeoutWarnSeconds,
      enabled: window.ProcessMaker.AccountTimeoutEnabled,
    },
  });

  const isSameDevice = (e) => {
    const localDeviceId = Vue.$cookies.get(e.device_variable);
    const remoteDeviceId = e.device_id;
    return localDeviceId && localDeviceId === remoteDeviceId;
  };

  window.Echo.private(`ProcessMaker.Models.User.${window.ProcessMaker.user.id}`)
    .notification((token) => {
      ProcessMaker.pushNotification(token);
    })
    .listen(".SessionStarted", (e) => {
      const lifetime = parseInt(eval(e.lifetime));
      if (isSameDevice(e)) {
        window.ProcessMaker.AccountTimeoutWorker.postMessage({
          method: "start",
          data: {
            timeout: lifetime,
            warnSeconds: window.ProcessMaker.AccountTimeoutWarnSeconds,
            enabled: window.ProcessMaker.AccountTimeoutEnabled,
          },
        });
        if (window.ProcessMaker.closeSessionModal) {
          window.ProcessMaker.closeSessionModal();
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
        window.ProcessMaker.alert(message, "success", 0, false, false, link);
      } else {
        window.ProcessMaker.alert(e.message, "warning");
      }
    });
}
