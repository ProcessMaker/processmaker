const DEFAULT_CACHE_TTL = 5000;

const CACHEABLE_METHOD = "get";

const isObject = (value) => value && typeof value === "object" && !Array.isArray(value);

const isAbsoluteURL = (url) => /^[a-z][a-z\d+\-.]*:\/\//i.test(url);

const combineURLs = (baseURL, requestedURL = "") => {
  const normalizedBaseURL = baseURL || "";

  if (!normalizedBaseURL || isAbsoluteURL(requestedURL)) {
    return requestedURL;
  }

  return `${normalizedBaseURL.replace(/\/+$/, "")}/${requestedURL.replace(/^\/+/, "")}`;
};

const normalizeHeaderName = (headers, headerName) => {
  const normalizedHeaders = headers || {};
  const match = Object.keys(normalizedHeaders).find((key) => key.toLowerCase() === headerName.toLowerCase());
  return match ? normalizedHeaders[match] : undefined;
};

const encodeValue = (value) => {
  if (value instanceof Date) {
    return value.toISOString();
  }

  if (isObject(value)) {
    return JSON.stringify(value);
  }

  return value;
};

const serializeParams = (params, paramsSerializer) => {
  if (!params) {
    return "";
  }

  if (typeof paramsSerializer === "function") {
    return paramsSerializer(params);
  }

  if (typeof URLSearchParams !== "undefined" && params instanceof URLSearchParams) {
    return params.toString();
  }

  return Object.keys(params)
    .sort()
    .reduce((parts, key) => {
      const value = params[key];

      if (value === null || typeof value === "undefined") {
        return parts;
      }

      const values = Array.isArray(value) ? value : [value];

      values.forEach((entry) => {
        parts.push(`${encodeURIComponent(key)}=${encodeURIComponent(encodeValue(entry))}`);
      });

      return parts;
    }, [])
    .join("&");
};

const appendParams = (url, params, paramsSerializer) => {
  const serializedParams = serializeParams(params, paramsSerializer);

  if (!serializedParams) {
    return url;
  }

  return `${url}${url.includes("?") ? "&" : "?"}${serializedParams}`;
};

const cloneData = (data) => {
  if (!data || typeof data !== "object") {
    return data;
  }

  if (typeof structuredClone === "function") {
    try {
      return structuredClone(data);
    } catch (error) {
      // Fall through to JSON cloning for plain response payloads.
    }
  }

  try {
    return JSON.parse(JSON.stringify(data));
  } catch (error) {
    return data;
  }
};

const cloneResponse = (response) => ({
  ...response,
  data: cloneData(response.data),
  headers: response.headers ? { ...response.headers } : response.headers,
});

export const buildApiClientCacheKey = (config = {}) => {
  const url = appendParams(
    combineURLs(config.baseURL, config.url || ""),
    config.params,
    config.paramsSerializer,
  );
  const headers = config.headers || {};
  const relevantConfig = {
    responseType: config.responseType || "",
    accept: normalizeHeaderName(headers, "Accept") || "",
  };

  return {
    key: `${url}|${JSON.stringify(relevantConfig)}`,
    url,
  };
};

export const installApiClientCache = (apiClient) => {
  if (!apiClient || apiClient.cache) {
    return apiClient;
  }

  const client = apiClient;
  const responseCache = new Map();
  const pendingRequests = new Map();
  const originalAdapter = client.defaults.adapter;

  let globallyEnabled = false;
  let disabled = false;
  let debugEnabled = false;

  const debug = (config, message, details = {}) => {
    if (!debugEnabled && !(config.cache && config.cache.debug)) {
      return;
    }

    // eslint-disable-next-line no-console
    console.log(`[ProcessMaker.apiClient.cache] ${message}`, details);
  };

  const cleanup = () => {
    const now = Date.now();

    responseCache.forEach((entry, key) => {
      if (entry.expiresAt <= now) {
        responseCache.delete(key);
      }
    });
  };

  const invalidateByMatcher = (matcher) => {
    responseCache.forEach((entry, key) => {
      if (matcher(key, entry)) {
        responseCache.delete(key);
      }
    });
  };

  const cache = {
    DEFAULT_CACHE_TTL,
    get enabled() {
      return globallyEnabled && !disabled;
    },
    get disabled() {
      return disabled;
    },
    get debug() {
      return debugEnabled;
    },
    enable() {
      globallyEnabled = true;
      disabled = false;
      debug({}, "cache enabled globally");
    },
    disable() {
      disabled = true;
      debug({}, "cache disabled for current window");
    },
    enableDebug() {
      debugEnabled = true;
      debug({}, "debug logging enabled");
    },
    disableDebug() {
      debug({}, "debug logging disabled");
      debugEnabled = false;
    },
    clear() {
      responseCache.clear();
      pendingRequests.clear();
      debug({}, "cache cleared");
    },
    cleanup,
    invalidate(urlOrKey) {
      invalidateByMatcher((key, entry) => key === urlOrKey || entry.url === urlOrKey);
      debug({}, "cache invalidated", { urlOrKey });
    },
    invalidateByPattern(pattern) {
      if (pattern instanceof RegExp) {
        invalidateByMatcher((key, entry) => pattern.test(key) || pattern.test(entry.url));
        debug({}, "cache invalidated by RegExp pattern", { pattern });
        return;
      }

      invalidateByMatcher((key, entry) => key.includes(pattern) || entry.url.includes(pattern));
      debug({}, "cache invalidated by pattern", { pattern });
    },
  };

  const isCacheEnabledForRequest = (config) => {
    if (disabled || (config.cache && config.cache.enabled === false)) {
      return false;
    }

    return Boolean(config.cache && config.cache.enabled === true) || globallyEnabled;
  };

  const getTTL = (config) => {
    const ttl = Number(config.cache && config.cache.ttl);
    return Number.isFinite(ttl) && ttl > 0 ? ttl : DEFAULT_CACHE_TTL;
  };

  client.DEFAULT_CACHE_TTL = DEFAULT_CACHE_TTL;
  client.cache = cache;
  client.defaults.adapter = (config) => {
    const method = (config.method || CACHEABLE_METHOD).toLowerCase();

    if (method !== CACHEABLE_METHOD || !isCacheEnabledForRequest(config)) {
      debug(config, "bypassing cache", {
        method,
        url: config.url,
        reason: method !== CACHEABLE_METHOD ? "non-get request" : "cache disabled",
      });
      return originalAdapter(config);
    }

    cleanup();

    const { key, url } = buildApiClientCacheKey(config);
    const cachedResponse = responseCache.get(key);

    if (cachedResponse && cachedResponse.expiresAt > Date.now()) {
      debug(config, "cache hit", {
        key,
        url,
        expiresAt: cachedResponse.expiresAt,
      });
      return Promise.resolve(cloneResponse(cachedResponse.response));
    }

    if (pendingRequests.has(key)) {
      debug(config, "deduplicated in-flight request", { key, url });
      return pendingRequests.get(key).then(cloneResponse);
    }

    debug(config, "cache miss; sending network request", { key, url });

    const request = originalAdapter(config)
      .then((response) => {
        responseCache.set(key, {
          key,
          url,
          response: cloneResponse(response),
          expiresAt: Date.now() + getTTL(config),
        });

        debug(config, "response cached", {
          key,
          url,
          ttl: getTTL(config),
          status: response.status,
        });

        return cloneResponse(response);
      })
      .catch((error) => {
        debug(config, "request failed; response not cached", {
          key,
          url,
          message: error.message,
          status: error.response && error.response.status,
        });

        return Promise.reject(error);
      })
      .finally(() => {
        pendingRequests.delete(key);
        debug(config, "in-flight request removed", { key, url });
      });

    pendingRequests.set(key, request);

    return request.then(cloneResponse);
  };

  return client;
};

export default installApiClientCache;
