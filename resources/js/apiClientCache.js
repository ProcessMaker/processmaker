/**
 * Default number of milliseconds a successful GET response remains reusable.
 */
const DEFAULT_CACHE_TTL = 5000;

/**
 * HTTP method eligible for response caching and request deduplication.
 */
const CACHEABLE_METHOD = "get";

/**
 * Determines whether a value is a plain object-like value for parameter encoding.
 *
 * @param {*} value
 * @returns {boolean}
 */
const isObject = (value) => value && typeof value === "object" && !Array.isArray(value);

/**
 * Checks whether a URL already includes a protocol and host.
 *
 * @param {string} url
 * @returns {boolean}
 */
const isAbsoluteURL = (url) => /^[a-z][a-z\d+\-.]*:\/\//i.test(url);

/**
 * Combines Axios baseURL and request URL while preserving absolute request URLs.
 *
 * @param {string} baseURL
 * @param {string} requestedURL
 * @returns {string}
 */
const combineURLs = (baseURL, requestedURL = "") => {
  const normalizedBaseURL = baseURL || "";

  if (!normalizedBaseURL || isAbsoluteURL(requestedURL)) {
    return requestedURL;
  }

  return `${normalizedBaseURL.replace(/\/+$/, "")}/${requestedURL.replace(/^\/+/, "")}`;
};

/**
 * Reads a header value case-insensitively from an Axios headers object.
 *
 * @param {object} headers
 * @param {string} headerName
 * @returns {*}
 */
const normalizeHeaderName = (headers, headerName) => {
  const normalizedHeaders = headers || {};
  const match = Object.keys(normalizedHeaders).find((key) => key.toLowerCase() === headerName.toLowerCase());
  return match ? normalizedHeaders[match] : undefined;
};

/**
 * Converts a query parameter value into a stable string representation.
 *
 * @param {*} value
 * @returns {*}
 */
const encodeValue = (value) => {
  if (value instanceof Date) {
    return value.toISOString();
  }

  if (isObject(value)) {
    return JSON.stringify(value);
  }

  return value;
};

/**
 * Serializes query parameters in a stable order unless Axios provides a custom serializer.
 *
 * @param {object|URLSearchParams} params
 * @param {Function} paramsSerializer
 * @returns {string}
 */
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

/**
 * Appends serialized query parameters to a URL.
 *
 * @param {string} url
 * @param {object|URLSearchParams} params
 * @param {Function} paramsSerializer
 * @returns {string}
 */
const appendParams = (url, params, paramsSerializer) => {
  const serializedParams = serializeParams(params, paramsSerializer);

  if (!serializedParams) {
    return url;
  }

  return `${url}${url.includes("?") ? "&" : "?"}${serializedParams}`;
};

/**
 * Clones response data so cached responses are not mutated by consumers.
 *
 * @param {*} data
 * @returns {*}
 */
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

/**
 * Clones the Axios response fields that consumers commonly mutate.
 *
 * @param {object} response
 * @returns {object}
 */
const cloneResponse = (response) => ({
  ...response,
  data: cloneData(response.data),
  headers: response.headers ? { ...response.headers } : response.headers,
});

/**
 * Builds the cache lookup key and human-readable URL for an Axios request.
 *
 * The key includes URL, query parameters, response type, and Accept header so
 * requests that can produce different payload shapes do not share cache entries.
 *
 * @param {object} config Axios request configuration
 * @returns {{key: string, url: string}}
 */
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

/**
 * Installs response caching and in-flight request deduplication on an Axios client.
 *
 * The wrapper is implemented at the Axios adapter layer so normal Axios call
 * forms, interceptors, defaults, and helpers continue to work unchanged.
 *
 * @param {Function|object} apiClient Axios client instance
 * @returns {Function|object} The same Axios client instance
 */
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

  /**
   * Writes cache diagnostics only when global or per-request debugging is enabled.
   *
   * @param {object} config Axios request configuration
   * @param {string} message Debug message
   * @param {object} details Structured details for browser console inspection
   * @returns {void}
   */
  const debug = (config, message, details = {}) => {
    if (!debugEnabled && !(config.cache && config.cache.debug)) {
      return;
    }

    // eslint-disable-next-line no-console
    console.log(`[ProcessMaker.apiClient.cache] ${message}`, details);
  };

  /**
   * Removes expired response entries from the in-memory cache.
   *
   * @returns {void}
   */
  const cleanup = () => {
    const now = Date.now();

    responseCache.forEach((entry, key) => {
      if (entry.expiresAt <= now) {
        responseCache.delete(key);
      }
    });
  };

  /**
   * Invalidates response entries that satisfy a caller-provided predicate.
   *
   * @param {Function} matcher Predicate receiving the cache key and entry
   * @returns {void}
   */
  const invalidateByMatcher = (matcher) => {
    responseCache.forEach((entry, key) => {
      if (matcher(key, entry)) {
        responseCache.delete(key);
      }
    });
  };

  const cache = {
    DEFAULT_CACHE_TTL,
    /**
     * Indicates whether cache is enabled globally for requests that do not opt in.
     *
     * @returns {boolean}
     */
    get enabled() {
      return globallyEnabled && !disabled;
    },
    /**
     * Indicates whether cache has been disabled for the current window.
     *
     * @returns {boolean}
     */
    get disabled() {
      return disabled;
    },
    /**
     * Indicates whether global cache debug logging is active.
     *
     * @returns {boolean}
     */
    get debug() {
      return debugEnabled;
    },
    /**
     * Enables cache for all eligible GET requests in the current window.
     *
     * @returns {void}
     */
    enable() {
      globallyEnabled = true;
      disabled = false;
      debug({}, "cache enabled globally");
    },
    /**
     * Disables cache and deduplication for the current window.
     *
     * @returns {void}
     */
    disable() {
      disabled = true;
      debug({}, "cache disabled for current window");
    },
    /**
     * Enables cache diagnostic logging for the current window.
     *
     * @returns {void}
     */
    enableDebug() {
      debugEnabled = true;
      debug({}, "debug logging enabled");
    },
    /**
     * Disables cache diagnostic logging for the current window.
     *
     * @returns {void}
     */
    disableDebug() {
      debug({}, "debug logging disabled");
      debugEnabled = false;
    },
    /**
     * Clears cached responses and tracked in-flight requests.
     *
     * @returns {void}
     */
    clear() {
      responseCache.clear();
      pendingRequests.clear();
      debug({}, "cache cleared");
    },
    cleanup,
    /**
     * Invalidates an exact cache key or URL.
     *
     * @param {string} urlOrKey
     * @returns {void}
     */
    invalidate(urlOrKey) {
      invalidateByMatcher((key, entry) => key === urlOrKey || entry.url === urlOrKey);
      debug({}, "cache invalidated", { urlOrKey });
    },
    /**
     * Invalidates cache entries whose key or URL matches a string or RegExp pattern.
     *
     * @param {string|RegExp} pattern
     * @returns {void}
     */
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

  /**
   * Determines whether a request should use cache behavior.
   *
   * @param {object} config Axios request configuration
   * @returns {boolean}
   */
  const isCacheEnabledForRequest = (config) => {
    if (disabled || (config.cache && config.cache.enabled === false)) {
      return false;
    }

    return Boolean(config.cache && config.cache.enabled === true) || globallyEnabled;
  };

  /**
   * Resolves the per-request TTL, falling back to the default duration.
   *
   * @param {object} config Axios request configuration
   * @returns {number}
   */
  const getTTL = (config) => {
    const ttl = Number(config.cache && config.cache.ttl);
    return Number.isFinite(ttl) && ttl > 0 ? ttl : DEFAULT_CACHE_TTL;
  };

  client.DEFAULT_CACHE_TTL = DEFAULT_CACHE_TTL;
  client.cache = cache;
  /**
   * Axios adapter wrapper that serves cached GETs, joins duplicate in-flight GETs,
   * or delegates to the original adapter for all other requests.
   *
   * @param {object} config Axios request configuration
   * @returns {Promise<object>}
   */
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
      return pendingRequests.get(key);
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

    return request;
  };

  return client;
};

export default installApiClientCache;
