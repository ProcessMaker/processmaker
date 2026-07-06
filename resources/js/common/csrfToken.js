/**
 * Read the current CSRF token from memory or the meta tag.
 */
export const getCsrfToken = () => {
  if (globalThis.ProcessMaker?.__pmXsrfToken) {
    return globalThis.ProcessMaker.__pmXsrfToken;
  }

  const meta = globalThis.document?.head?.querySelector('meta[name="csrf-token"]');
  return meta?.content || null;
};

const setRequestCsrfHeader = (config, token) => {
  if (!token) {
    return;
  }

  // Per-request header overrides axios defaults (even when defaults are frozen).
  // eslint-disable-next-line no-param-reassign
  config.headers = config.headers || {};
  // eslint-disable-next-line no-param-reassign
  config.headers["X-CSRF-TOKEN"] = token;

  if (config.headers.common) {
    // eslint-disable-next-line no-param-reassign
    config.headers.common["X-CSRF-TOKEN"] = token;
  }
};

const safeStorageGetItem = (key) => {
  try {
    return globalThis.localStorage?.getItem(key);
  } catch (error) {
    return null;
  }
};

const isSessionRenewalDebugEnabled = () => safeStorageGetItem("pm:session:debug") === "1";

const getRequestDebugData = (config) => {
  const requestUrl = getRequestUrl(config);

  return {
    method: config?.method || "get",
    url: requestUrl ? `${requestUrl.pathname}${requestUrl.search}` : config?.url,
  };
};

const sessionRenewalDebugLog = (...args) => {
  if (isSessionRenewalDebugEnabled()) {
    console.info("[SessionRenewal]", ...args);
  }
};

/**
 * Apply CSRF token to all client-side sources axios may read from.
 */
export const applyCsrfToken = (token) => {
  if (!token) {
    return false;
  }

  globalThis.ProcessMaker = globalThis.ProcessMaker || {};

  globalThis.ProcessMaker.__pmXsrfToken = token;

  const meta = globalThis.document?.head?.querySelector('meta[name="csrf-token"]');
  if (meta) {
    meta.setAttribute("content", token);
  }

  try {
    if (globalThis.ProcessMaker?.apiClient?.defaults?.headers?.common) {
      globalThis.ProcessMaker.apiClient.defaults.headers.common["X-CSRF-TOKEN"] = token;
    }
  } catch (e) {
    // If defaults are readonly, the request interceptor will still set the header.
  }

  sessionRenewalDebugLog("csrf:applied", {
    hasApiClientDefaults: !!globalThis.ProcessMaker?.apiClient?.defaults?.headers?.common,
    hasMeta: !!meta,
  });

  return true;
};

const getRequestUrl = (config) => {
  if (typeof config?.url !== "string" || !config.url) {
    return null;
  }

  try {
    const currentOrigin = globalThis.location?.origin;
    const baseURL = config.baseURL
      ? new URL(config.baseURL, currentOrigin).href
      : currentOrigin;

    return new URL(config.url, baseURL);
  } catch (error) {
    return null;
  }
};

const getSessionRenewalSkipReason = (config) => {
  if (config?.skipSessionRenewal || config?.__skipSessionRenewal) {
    return "request-marked-skip";
  }

  const requestUrl = getRequestUrl(config);
  if (!requestUrl) {
    return "invalid-url";
  }

  const currentOrigin = globalThis.location?.origin;
  if (currentOrigin && requestUrl.origin !== currentOrigin) {
    return "external-origin";
  }

  const excludedPath = [
    "/keep-alive",
    "/logout",
    "/login",
    "/debug",
  ].find((path) => requestUrl.pathname === path || requestUrl.pathname.startsWith(`${path}/`));

  return excludedPath ? `excluded-path:${excludedPath}` : null;
};

const getSessionRenewalStatus = (sessionSync) => {
  if (typeof sessionSync?.getRequestRenewalStatus === "function") {
    return sessionSync.getRequestRenewalStatus();
  }

  return {
    remainingSeconds: typeof sessionSync?.getRemainingSeconds === "function"
      ? sessionSync.getRemainingSeconds()
      : null,
    renewalThresholdSeconds: null,
    shouldRenew: typeof sessionSync?.shouldRenewBeforeRequest === "function"
      && sessionSync.shouldRenewBeforeRequest(),
  };
};

const getRemainingSeconds = (sessionSync) => getSessionRenewalStatus(sessionSync).remainingSeconds;

const logSessionRenewalSkipped = (config, reason) => {
  sessionRenewalDebugLog("request:skip-renewal", {
    ...getRequestDebugData(config),
    reason,
  });
};

const handleExpiredSession = () => {
  sessionRenewalDebugLog("session:expired");

  if (globalThis.ProcessMaker?.sessionSync?.clearWarningState) {
    globalThis.ProcessMaker.sessionSync.clearWarningState();
  }

  if (globalThis.ProcessMaker?.sessionSync?.broadcast) {
    globalThis.ProcessMaker.sessionSync.broadcast("expired");
  }

  if (globalThis.location) {
    globalThis.location.href = "/logout";
  }
};

let pendingSessionRenewal = null;

const renewSessionBeforeRequest = async (apiClient) => {
  if (pendingSessionRenewal) {
    sessionRenewalDebugLog("keep-alive:join-pending");
    return pendingSessionRenewal;
  }

  const sessionSync = globalThis.ProcessMaker?.sessionSync;
  const renewalStatus = getSessionRenewalStatus(sessionSync);
  sessionRenewalDebugLog("keep-alive:start", {
    remainingSeconds: renewalStatus.remainingSeconds,
    renewalThresholdSeconds: renewalStatus.renewalThresholdSeconds,
  });

  pendingSessionRenewal = apiClient
    .post("/keep-alive", {}, {
      baseURL: "",
      skipSessionRenewal: true,
      __skipSessionRenewal: true,
    })
    .then((response) => {
      const { token } = response.data || {};

      if (token) {
        applyCsrfToken(token);
      }

      const timeout = globalThis.ProcessMaker?.AccountTimeoutLength;
      if (sessionSync?.renewSession && timeout) {
        sessionSync.renewSession(timeout);
      }

      sessionRenewalDebugLog("keep-alive:success", {
        hasToken: !!token,
        timeout,
      });

      return response;
    })
    .catch((error) => {
      const status = error?.response?.status;
      if (status === 401 || status === 419) {
        sessionRenewalDebugLog("keep-alive:auth-failed", { status });
        handleExpiredSession();
        throw error;
      }

      sessionRenewalDebugLog("keep-alive:failed-continue", {
        status,
        message: error?.message,
      });
      return null;
    })
    .finally(() => {
      sessionRenewalDebugLog("keep-alive:finished");
      pendingSessionRenewal = null;
    });

  return pendingSessionRenewal;
};

/**
 * Renew the Laravel session and CSRF token right before an API request when the
 * known session lifetime is close to expiring.
 */
export const attachSessionRenewalInterceptor = (apiClient) => {
  if (!apiClient?.interceptors?.request) {
    return;
  }

  return apiClient.interceptors.request.use(async (config) => {
    const sessionSync = globalThis.ProcessMaker?.sessionSync;
    const skipReason = getSessionRenewalSkipReason(config);
    const renewalStatus = getSessionRenewalStatus(sessionSync);

    if (skipReason) {
      logSessionRenewalSkipped(config, skipReason);
      return config;
    }

    if (!renewalStatus.shouldRenew) {
      sessionRenewalDebugLog("request:continue-with-current-session", {
        ...getRequestDebugData(config),
        remainingSeconds: renewalStatus.remainingSeconds,
        renewalThresholdSeconds: renewalStatus.renewalThresholdSeconds,
      });
      return config;
    }

    sessionRenewalDebugLog("request:renew-before-send", {
      ...getRequestDebugData(config),
      remainingSeconds: renewalStatus.remainingSeconds,
      renewalThresholdSeconds: renewalStatus.renewalThresholdSeconds,
    });

    await renewSessionBeforeRequest(apiClient);

    return config;
  });
};

/**
 * Attach a request interceptor so every request uses the latest CSRF token.
 * Register this BEFORE other interceptors so it runs LAST in the axios chain.
 */
export const attachCsrfRequestInterceptor = (apiClient) => {
  if (!apiClient?.interceptors?.request) {
    return;
  }

  apiClient.interceptors.request.use((config) => {
    const token = getCsrfToken();
    if (token) {
      setRequestCsrfHeader(config, token);
    }

    return config;
  });
};
