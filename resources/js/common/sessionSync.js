export const initSessionSync = ({
  userId,
  isProd,
  timeoutScript,
  accountTimeoutLength,
  accountTimeoutWarnSeconds,
  accountTimeoutEnabled,
  Vue,
  Echo,
  pushNotification,
  alert,
  getSessionModal,
  getCloseSessionModal,
  getNavbar,
}) => {
  if (!userId) {
    return null;
  }

  const sessionChannelName = "pm-session-sync";
  const sessionLeaderKey = "pm:session:leader";
  const sessionStateKey = "pm:session:state";
  const sessionWarningKey = "pm:session:warning";
  // Track keep-alive progress across tabs.
  const sessionRenewingKey = "pm:session:renewing";
  const sessionSuppressKey = "pm:session:suppress-warning";
  const sessionMessageKey = "pm:session:message";
  const sessionTabId = `${Date.now()}-${Math.random().toString(16).slice(2)}`;
  const leaderHeartbeatMs = 4000;
  const leaderTtlMs = 8000;
  const sessionDebugEnabled = localStorage.getItem("pm:session:debug") === "1";
  const sessionDebugLog = (...args) => {
    if (sessionDebugEnabled && !isProd) {
      console.info("[SessionSync]", `[tab:${sessionTabId}]`, ...args);
    }
  };

  sessionDebugLog("worker:init", { timeoutScript });
  const AccountTimeoutWorker = new Worker(timeoutScript);
  sessionDebugLog("worker:created");

  const resolveSessionModal = () => (typeof getSessionModal === "function" ? getSessionModal() : null);
  const resolveCloseSessionModal = () => (typeof getCloseSessionModal === "function" ? getCloseSessionModal() : null);

  const readStorageJson = (key) => {
    try {
      const raw = localStorage.getItem(key);
      return raw ? JSON.parse(raw) : null;
    } catch (error) {
      return null;
    }
  };

  const writeStorageJson = (key, value) => {
    try {
      localStorage.setItem(key, JSON.stringify(value));
    } catch (error) {
      // ignore storage failures (private mode or disabled)
    }
  };

  const removeStorageKey = (key) => {
    try {
      localStorage.removeItem(key);
    } catch (error) {
      // ignore storage failures (private mode or disabled)
    }
  };

  let sessionState = {
    timeout: accountTimeoutLength,
    startedAt: Date.now(),
  };

  const refreshSessionStateFromStorage = () => {
    const storedSessionState = readStorageJson(sessionStateKey);
    if (storedSessionState?.timeout && storedSessionState?.startedAt) {
      const storedTimeout = Number(storedSessionState.timeout);
      const storedStartedAt = Number(storedSessionState.startedAt);
      const elapsedMinutes = (Date.now() - storedStartedAt) / 60000;
      if (storedTimeout > 0 && elapsedMinutes < storedTimeout) {
        sessionState = storedSessionState;
      } else {
        sessionDebugLog("session-state:stale", { storedSessionState, elapsedMinutes });
        writeStorageJson(sessionStateKey, sessionState);
      }
    } else {
      writeStorageJson(sessionStateKey, sessionState);
    }
    sessionDebugLog("session-state:refresh", sessionState);
    return sessionState;
  };

  refreshSessionStateFromStorage();

  const setSessionState = (timeoutMinutes) => {
    sessionState = {
      timeout: timeoutMinutes,
      startedAt: Date.now(),
    };
    writeStorageJson(sessionStateKey, sessionState);
    sessionDebugLog("session-state", sessionState);
  };

  let warningState = readStorageJson(sessionWarningKey);
  let renewingState = readStorageJson(sessionRenewingKey);
  let suppressWarningState = readStorageJson(sessionSuppressKey);

  const refreshWarningStateFromStorage = () => {
    const storedWarningState = readStorageJson(sessionWarningKey);
    if (storedWarningState?.time && storedWarningState?.ts) {
      // Clear stale warning from previous session.
      if (sessionState?.startedAt && storedWarningState.ts < sessionState.startedAt) {
        warningState = null;
        removeStorageKey(sessionWarningKey);
      } else {
        warningState = storedWarningState;
      }
    } else {
      warningState = null;
    }
    sessionDebugLog("warning-state:refresh", warningState);
    return warningState;
  };

  const setWarningState = (timeSeconds) => {
    warningState = {
      time: timeSeconds,
      ts: Date.now(),
    };
    writeStorageJson(sessionWarningKey, warningState);
    sessionDebugLog("warning-state:set", warningState);
  };

  const clearWarningState = () => {
    warningState = null;
    removeStorageKey(sessionWarningKey);
    sessionDebugLog("warning-state:clear");
  };

  const syncRenewingUi = () => {
    const navbar = typeof getNavbar === "function" ? getNavbar() : null;
    if (navbar) {
      navbar.sessionIsRenewing = !!renewingState?.isRenewing;
    }
  };

  const refreshRenewingStateFromStorage = () => {
    const storedRenewingState = readStorageJson(sessionRenewingKey);
    renewingState = storedRenewingState?.isRenewing ? storedRenewingState : null;
    syncRenewingUi();
    return renewingState;
  };

  const setRenewingState = (isRenewing) => {
    if (isRenewing) {
      renewingState = {
        isRenewing: true,
        ts: Date.now(),
      };
      writeStorageJson(sessionRenewingKey, renewingState);
    } else {
      renewingState = null;
      removeStorageKey(sessionRenewingKey);
      setSuppressWarning(1000);
    }
    syncRenewingUi();
  };

  const refreshSuppressWarningState = () => {
    const storedSuppressState = readStorageJson(sessionSuppressKey);
    suppressWarningState = storedSuppressState?.until ? storedSuppressState : null;
    return suppressWarningState;
  };

  const setSuppressWarning = (durationMs) => {
    suppressWarningState = {
      until: Date.now() + durationMs,
    };
    writeStorageJson(sessionSuppressKey, suppressWarningState);
  };

  const sessionChannel = "BroadcastChannel" in window ? new BroadcastChannel(sessionChannelName) : null;
  const recentMessageIds = new Map();
  const recentMessageTtlMs = 5000;
  const maxRecentMessageIds = 100;

  const shouldSkipMessage = (message) => {
    if (!message?.id) {
      return false;
    }
    const now = Date.now();
    const lastSeen = recentMessageIds.get(message.id);
    if (lastSeen && now - lastSeen < recentMessageTtlMs) {
      return true;
    }
    recentMessageIds.set(message.id, now);
    if (recentMessageIds.size > maxRecentMessageIds) {
      for (const [id, ts] of recentMessageIds) {
        if (now - ts > recentMessageTtlMs) {
          recentMessageIds.delete(id);
        }
        if (recentMessageIds.size <= maxRecentMessageIds) {
          break;
        }
      }
    }
    return false;
  };

  const broadcastSessionEvent = (type, data = {}) => {
    const message = {
      id: `${sessionTabId}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
      type,
      data,
      from: sessionTabId,
      ts: Date.now(),
    };

    sessionDebugLog("broadcast", message);
    writeStorageJson(sessionMessageKey, message);
    if (sessionChannel) {
      sessionChannel.postMessage(message);
    } else {
      // storage already written above
    }
  };

  const getLeader = () => readStorageJson(sessionLeaderKey);

  const writeLeader = () => {
    writeStorageJson(sessionLeaderKey, {
      tabId: sessionTabId,
      ts: Date.now(),
    });
    sessionDebugLog("leader:claim", { tabId: sessionTabId });
  };

  const isLeader = () => {
    const leader = getLeader();
    return document.visibilityState === "visible"
      && !!leader
      && leader.tabId === sessionTabId
      && Date.now() - leader.ts < leaderTtlMs;
  };

  let workerStarted = false;
  const ensureWorkerRunning = (reason) => {
    if (workerStarted) {
      return;
    }
    workerStarted = true;
    refreshSessionStateFromStorage();
    refreshWarningStateFromStorage();
    sessionDebugLog("worker:ensure", { reason, sessionState });
    startTimeoutWorker(sessionState.timeout);
    showWarningIfActive();
  };

  const markActivity = (source) => {
    setSessionState(accountTimeoutLength);
    clearWarningState();
    broadcastSessionEvent("activity", { timeout: accountTimeoutLength, source });
    sessionDebugLog("activity", { source, timeout: accountTimeoutLength });
    if (isLeader()) {
      ensureWorkerRunning(`activity:${source}`);
    }
  };

  const getRemainingTimeout = (timeoutMinutes) => {
    const elapsedMinutes = (Date.now() - sessionState.startedAt) / 60000;
    const remaining = timeoutMinutes - elapsedMinutes;
    return Math.max(0, remaining);
  };

  const getRemainingWarningTime = () => {
    if (!warningState?.time || !warningState?.ts) {
      return 0;
    }
    const elapsedSeconds = Math.floor((Date.now() - warningState.ts) / 1000);
    return Math.max(0, warningState.time - elapsedSeconds);
  };

  const startTimeoutWorker = (timeoutMinutes) => {
    const remaining = getRemainingTimeout(timeoutMinutes);
    sessionDebugLog("worker:start", { timeoutMinutes, remaining });
    if (remaining <= 0) {
      broadcastSessionEvent("expired");
      window.location = "/logout?timeout=true";
      return;
    }

    AccountTimeoutWorker.postMessage({
      method: "start",
      data: {
        timeout: remaining,
        warnSeconds: accountTimeoutWarnSeconds,
        enabled: accountTimeoutEnabled,
      },
    });
  };

  const showWarningIfActive = () => {
    const remainingTime = getRemainingWarningTime();
    if (remainingTime <= 0) {
      sessionDebugLog("warning:skip", { remainingTime });
      clearWarningState();
      const closeSessionModal = resolveCloseSessionModal();
      if (closeSessionModal) {
        closeSessionModal();
      }
      setRenewingState(false);
      return;
    }
    refreshRenewingStateFromStorage();
    if (renewingState?.isRenewing) {
      return;
    }
    refreshSuppressWarningState();
    if (suppressWarningState?.until && Date.now() < suppressWarningState.until) {
      return;
    }
    sessionDebugLog("warning:show", { remainingTime });
    const sessionModal = resolveSessionModal();
    // Guard for layouts that don't include the session modal.
    if (typeof sessionModal === "function") {
      sessionModal(
        "Session Warning",
        "<p>Your user session is expiring. If your session expires, all of your unsaved data will be lost.</p><p>Would you like to stay connected?</p>",
        remainingTime,
        accountTimeoutWarnSeconds,
      );
    }
  };

  const handleSessionMessage = (message) => {
    if (!message || message.from === sessionTabId) {
      return;
    }
    if (shouldSkipMessage(message)) {
      return;
    }

    sessionDebugLog("receive", message);
    if (message.type === "warning") {
      const time = Number(message.data?.time);
      if (time) {
        setWarningState(time);
        if (document.visibilityState === "visible") {
          showWarningIfActive();
        }
      }
      return;
    }

    if (message.type === "renewing") {
      const isRenewing = !!message.data?.isRenewing;
      setRenewingState(isRenewing);
      if (isRenewing) {
        clearWarningState();
        const closeSessionModal = resolveCloseSessionModal();
        if (closeSessionModal) {
          closeSessionModal();
        }
      }
      return;
    }

    if (message.type === "renewed" || message.type === "started" || message.type === "activity") {
      const timeout = Number(message.data?.timeout) || accountTimeoutLength;
      clearWarningState();
      setRenewingState(false);
      setSuppressWarning(1000);
      setSessionState(timeout);
      const closeSessionModal = resolveCloseSessionModal();
      if (closeSessionModal) {
        closeSessionModal();
      }
      if (isLeader()) {
        startTimeoutWorker(timeout);
      }
      return;
    }

    if (message.type === "logout") {
      clearWarningState();
      setRenewingState(false);
      window.location = "/logout";
    }

    if (message.type === "expired") {
      clearWarningState();
      setRenewingState(false);
      window.location = "/logout?timeout=true";
    }
  };

  if (sessionChannel) {
    sessionChannel.onmessage = (event) => handleSessionMessage(event.data);
  }

  window.addEventListener("storage", (event) => {
    if (event.key !== sessionMessageKey || !event.newValue) {
      return;
    }

    handleSessionMessage(readStorageJson(sessionMessageKey));
  });

  AccountTimeoutWorker.onmessage = (e) => {
    if (!isLeader()) {
      return;
    }

    if (e.data.method === "countdown") {
      sessionDebugLog("worker:countdown", e.data.data);
      setWarningState(e.data.data.time);
      showWarningIfActive();
      broadcastSessionEvent("warning", { time: e.data.data.time });
    }
    if (e.data.method === "timedOut") {
      sessionDebugLog("worker:timedOut");
      refreshSessionStateFromStorage();
      const remaining = getRemainingTimeout(sessionState.timeout);
      sessionDebugLog("worker:timedOut:check", { remaining, sessionState });
      if (remaining > 0) {
        startTimeoutWorker(sessionState.timeout);
        return;
      }
      clearWarningState();
      broadcastSessionEvent("expired");
      window.location = "/logout?timeout=true";
    }
  };

  let wasLeader = false;
  const updateLeadership = () => {
    const leader = getLeader();
    const now = Date.now();
    const isVisible = document.visibilityState === "visible";
    sessionDebugLog("leader:check", {
      isVisible,
      leader,
      now,
    });
    if (isVisible) {
      const leaderExpired = !leader || (now - leader.ts >= leaderTtlMs);
      if (leaderExpired || leader?.tabId === sessionTabId) {
        writeLeader();
      }
      refreshWarningStateFromStorage();
      showWarningIfActive();
    }

    const leaderNow = isLeader();
    if (leaderNow) {
      ensureWorkerRunning("leadership");
    }
    if (leaderNow !== wasLeader) {
      wasLeader = leaderNow;
      sessionDebugLog("leader:changed", { isLeader: leaderNow });
      if (leaderNow) {
        ensureWorkerRunning("leadership-change");
      } else {
        const closeSessionModal = resolveCloseSessionModal();
        if (closeSessionModal) {
          workerStarted = false;
          closeSessionModal();
        }
      }
    }
  };

  updateLeadership();
  if (isLeader()) {
    markActivity("load");
    ensureWorkerRunning("load");
  }
  setInterval(updateLeadership, leaderHeartbeatMs);
  window.addEventListener("visibilitychange", () => {
    updateLeadership();
    // Keep warning state in sync when switching tabs.
    refreshWarningStateFromStorage();
    showWarningIfActive();
    if (isLeader()) {
      // Only the leader drives the worker countdown.
      refreshSessionStateFromStorage();
      startTimeoutWorker(sessionState.timeout);
    }
  });

  // Broadcast manual logout so all tabs close warning and redirect.
  document.addEventListener("click", (event) => {
    const logoutLink = event.target.closest('a[href="/logout"], a[href^="/logout?"]');
    if (!logoutLink) {
      return;
    }
    clearWarningState();
    broadcastSessionEvent("logout");
  });

  // Restart the timeout worker (when the user interacts with the page)
  const eventsTimeoutWorker = ["click", "keypress"];
  eventsTimeoutWorker.forEach((event) => {
    document.addEventListener(event, () => {
      if (!isLeader()) {
        sessionDebugLog("worker:restart:skip", { event });
        return;
      }
      markActivity(event);
      sessionDebugLog("worker:restart", { event });
      AccountTimeoutWorker.postMessage({ method: "restart" });
    });
  });

  const isSameDevice = (e) => {
    const localDeviceId = Vue.$cookies.get(e.device_variable);
    const remoteDeviceId = e.device_id;
    return localDeviceId && localDeviceId === remoteDeviceId;
  };

  if (Echo) {
    Echo.private(`ProcessMaker.Models.User.${userId}`)
      .notification((token) => {
        if (typeof pushNotification === "function") {
          pushNotification(token);
        }
      })
      .listen(".SessionStarted", (e) => {
        const lifetime = parseInt(eval(e.lifetime));
        if (!isSameDevice(e)) {
          return;
        }

        sessionDebugLog("event:session-started", { lifetime });
        setSessionState(lifetime);
        // Clear any stale warning on new login/session.
        clearWarningState();
        broadcastSessionEvent("started", { timeout: lifetime });
        const closeSessionModal = resolveCloseSessionModal();
        if (closeSessionModal) {
          closeSessionModal();
        }
        if (isLeader()) {
          startTimeoutWorker(lifetime);
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
        if (typeof alert !== "function") {
          return;
        }
        if (e.success) {
          const { link } = e;
          const { message } = e;
          alert(message, "success", 0, false, false, link);
        } else {
          alert(e.message, "warning");
        }
      });
  }

  return {
    AccountTimeoutLength: accountTimeoutLength,
    AccountTimeoutWarnSeconds: accountTimeoutWarnSeconds,
    AccountTimeoutWarnMinutes: accountTimeoutWarnSeconds / 60,
    AccountTimeoutEnabled: accountTimeoutEnabled,
    AccountTimeoutWorker,
    sessionSync: {
      broadcast: broadcastSessionEvent,
      isLeader,
      setSessionState,
      clearWarningState,
      setRenewingState,
    },
  };
};
