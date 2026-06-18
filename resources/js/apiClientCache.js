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
    enable() {
      globallyEnabled = true;
      disabled = false;
    },
    disable() {
      disabled = true;
    },
    clear() {
      responseCache.clear();
      pendingRequests.clear();
    },
    cleanup,
    invalidate(urlOrKey) {
      invalidateByMatcher((key, entry) => key === urlOrKey || entry.url === urlOrKey);
    },
    invalidateByPattern(pattern) {
      if (pattern instanceof RegExp) {
        invalidateByMatcher((key, entry) => pattern.test(key) || pattern.test(entry.url));
        return;
      }

      invalidateByMatcher((key, entry) => key.includes(pattern) || entry.url.includes(pattern));
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
      return originalAdapter(config);
    }

    cleanup();

    const { key, url } = buildApiClientCacheKey(config);
    const cachedResponse = responseCache.get(key);

    if (cachedResponse && cachedResponse.expiresAt > Date.now()) {
      return Promise.resolve(cloneResponse(cachedResponse.response));
    }

    if (pendingRequests.has(key)) {
      return pendingRequests.get(key).then(cloneResponse);
    }

    const request = originalAdapter(config)
      .then((response) => {
        responseCache.set(key, {
          key,
          url,
          response: cloneResponse(response),
          expiresAt: Date.now() + getTTL(config),
        });

        return cloneResponse(response);
      })
      .finally(() => {
        pendingRequests.delete(key);
      });

    pendingRequests.set(key, request);

    return request.then(cloneResponse);
  };

  return client;
};

export default installApiClientCache;
