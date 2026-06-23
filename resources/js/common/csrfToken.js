/**
 * Read the current CSRF token from memory or the meta tag.
 */
export const getCsrfToken = () => {
  if (window.ProcessMaker?.__pmXsrfToken) {
    return window.ProcessMaker.__pmXsrfToken;
  }

  const meta = document.head?.querySelector('meta[name="csrf-token"]');
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

/**
 * Apply CSRF token to all client-side sources axios may read from.
 */
export const applyCsrfToken = (token) => {
  if (!token) {
    return false;
  }

  window.ProcessMaker = window.ProcessMaker || {};

  window.ProcessMaker.__pmXsrfToken = token;

  const meta = document.head?.querySelector('meta[name="csrf-token"]');
  if (meta) {
    meta.setAttribute("content", token);
  }

  try {
    if (window.ProcessMaker?.apiClient?.defaults?.headers?.common) {
      window.ProcessMaker.apiClient.defaults.headers.common["X-CSRF-TOKEN"] = token;
    }
  } catch (e) {
    // If defaults are readonly, the request interceptor will still set the header.
  }

  return true;
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
