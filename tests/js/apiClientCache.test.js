import { buildApiClientCacheKey, installApiClientCache } from "../../resources/js/apiClientCache";

const createApiClient = (adapter) => ({
  defaults: {
    adapter,
  },
});

const createResponse = (data, config = {}) => ({
  data,
  status: 200,
  statusText: "OK",
  headers: {},
  config,
});

describe("apiClient cache", () => {
  afterEach(() => {
    jest.restoreAllMocks();
  });

  test("does not cache GET requests unless caching is enabled", async () => {
    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ count: 1 }))
      .mockResolvedValueOnce(createResponse({ count: 2 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = { method: "get", baseURL: "/api/1.0/", url: "tasks" };

    const first = await apiClient.defaults.adapter(config);
    const second = await apiClient.defaults.adapter(config);

    expect(adapter).toHaveBeenCalledTimes(2);
    expect(first.data).toEqual({ count: 1 });
    expect(second.data).toEqual({ count: 2 });
  });

  test("deduplicates concurrent cache-enabled GET requests", async () => {
    let resolveRequest;
    const adapter = jest.fn(() => new Promise((resolve) => {
      resolveRequest = resolve;
    }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "task_schema/584421",
      cache: { enabled: true },
    };

    const first = apiClient.defaults.adapter(config);
    const second = apiClient.defaults.adapter(config);

    expect(adapter).toHaveBeenCalledTimes(1);
    expect(first).toBe(second);

    resolveRequest(createResponse({ id: 584421 }, config));

    await expect(first).resolves.toMatchObject({ data: { id: 584421 } });
    await expect(second).resolves.toMatchObject({ data: { id: 584421 } });
  });

  test("uses the default TTL and refetches after it expires", async () => {
    let now = 1000;
    jest.spyOn(Date, "now").mockImplementation(() => now);

    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ count: 1 }))
      .mockResolvedValueOnce(createResponse({ count: 2 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "tasks",
      cache: { enabled: true },
    };

    const first = await apiClient.defaults.adapter(config);
    now = 5999;
    const second = await apiClient.defaults.adapter(config);
    now = 6000;
    const third = await apiClient.defaults.adapter(config);

    expect(apiClient.DEFAULT_CACHE_TTL).toBe(5000);
    expect(adapter).toHaveBeenCalledTimes(2);
    expect(first.data).toEqual({ count: 1 });
    expect(second.data).toEqual({ count: 1 });
    expect(third.data).toEqual({ count: 2 });
  });

  test("uses a per-request TTL override", async () => {
    let now = 1000;
    jest.spyOn(Date, "now").mockImplementation(() => now);

    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ count: 1 }))
      .mockResolvedValueOnce(createResponse({ count: 2 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "tasks",
      cache: { enabled: true, ttl: 30_000 },
    };

    await apiClient.defaults.adapter(config);
    now = 30_000;
    const cached = await apiClient.defaults.adapter(config);
    now = 31_000;
    const refetched = await apiClient.defaults.adapter(config);

    expect(adapter).toHaveBeenCalledTimes(2);
    expect(cached.data).toEqual({ count: 1 });
    expect(refetched.data).toEqual({ count: 2 });
  });

  test("does not cache failed GET requests", async () => {
    const error = new Error("Network failed");
    const adapter = jest.fn()
      .mockRejectedValueOnce(error)
      .mockResolvedValueOnce(createResponse({ count: 1 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "tasks",
      cache: { enabled: true },
    };

    await expect(apiClient.defaults.adapter(config)).rejects.toThrow("Network failed");
    await expect(apiClient.defaults.adapter(config)).resolves.toMatchObject({ data: { count: 1 } });

    expect(adapter).toHaveBeenCalledTimes(2);
  });

  test("bypasses cache and deduplication for non-GET requests", async () => {
    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ count: 1 }))
      .mockResolvedValueOnce(createResponse({ count: 2 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "post",
      baseURL: "/api/1.0/",
      url: "tasks",
      cache: { enabled: true },
    };

    await apiClient.defaults.adapter(config);
    await apiClient.defaults.adapter(config);

    expect(adapter).toHaveBeenCalledTimes(2);
  });

  test("globally enables caching while allowing a request to opt out", async () => {
    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ count: 1 }))
      .mockResolvedValueOnce(createResponse({ count: 2 }))
      .mockResolvedValueOnce(createResponse({ count: 3 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = { method: "get", baseURL: "/api/1.0/", url: "tasks" };

    apiClient.cache.enable();
    await apiClient.defaults.adapter(config);
    await apiClient.defaults.adapter(config);
    await apiClient.defaults.adapter({ ...config, cache: { enabled: false } });
    await apiClient.defaults.adapter({ ...config, cache: { enabled: false } });

    expect(adapter).toHaveBeenCalledTimes(3);
  });

  test("invalidates cached responses by exact URL and pattern", async () => {
    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ count: 1 }))
      .mockResolvedValueOnce(createResponse({ count: 2 }))
      .mockResolvedValueOnce(createResponse({ count: 3 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "tasks",
      params: { include: "data" },
      cache: { enabled: true },
    };

    await apiClient.defaults.adapter(config);
    apiClient.cache.invalidate("/api/1.0/tasks?include=data");
    const second = await apiClient.defaults.adapter(config);
    apiClient.cache.invalidateByPattern("/tasks");
    const third = await apiClient.defaults.adapter(config);

    expect(adapter).toHaveBeenCalledTimes(3);
    expect(second.data).toEqual({ count: 2 });
    expect(third.data).toEqual({ count: 3 });
  });

  test("disable bypasses even explicitly cache-enabled requests in the current window", async () => {
    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ count: 1 }))
      .mockResolvedValueOnce(createResponse({ count: 2 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "tasks",
      cache: { enabled: true },
    };

    apiClient.cache.disable();

    const first = await apiClient.defaults.adapter(config);
    const second = await apiClient.defaults.adapter(config);

    expect(adapter).toHaveBeenCalledTimes(2);
    expect(first.data).toEqual({ count: 1 });
    expect(second.data).toEqual({ count: 2 });
  });

  test("builds stable keys from URL, params, and relevant config", () => {
    const first = buildApiClientCacheKey({
      baseURL: "/api/1.0/",
      url: "tasks",
      params: { b: 2, a: 1 },
      headers: { Accept: "application/json" },
    });
    const second = buildApiClientCacheKey({
      baseURL: "/api/1.0/",
      url: "tasks",
      params: { a: 1, b: 2 },
      headers: { accept: "application/json" },
    });

    expect(first).toEqual(second);
    expect(first.url).toBe("/api/1.0/tasks?a=1&b=2");
  });

  test("keeps responses with distinct relevant configuration separate", async () => {
    const adapter = jest.fn()
      .mockResolvedValueOnce(createResponse({ format: "json" }))
      .mockResolvedValueOnce(createResponse({ format: "blob" }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const baseConfig = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "tasks",
      cache: { enabled: true },
    };

    const json = await apiClient.defaults.adapter({
      ...baseConfig,
      headers: { Accept: "application/json" },
    });
    const blob = await apiClient.defaults.adapter({
      ...baseConfig,
      headers: { Accept: "application/octet-stream" },
      responseType: "blob",
    });

    expect(adapter).toHaveBeenCalledTimes(2);
    expect(json.data).toEqual({ format: "json" });
    expect(blob.data).toEqual({ format: "blob" });
  });

  test("logs cache decisions when debug is enabled", async () => {
    const log = jest.spyOn(console, "log").mockImplementation(() => {});
    const adapter = jest.fn().mockResolvedValue(createResponse({ count: 1 }));
    const apiClient = installApiClientCache(createApiClient(adapter));
    const config = {
      method: "get",
      baseURL: "/api/1.0/",
      url: "tasks",
      cache: { enabled: true },
    };

    apiClient.cache.enableDebug();

    await apiClient.defaults.adapter(config);

    expect(log).toHaveBeenCalledWith(
      "[ProcessMaker.apiClient.cache] cache miss; sending network request",
      expect.objectContaining({ url: "/api/1.0/tasks" }),
    );
    expect(log).toHaveBeenCalledWith(
      "[ProcessMaker.apiClient.cache] response cached",
      expect.objectContaining({ status: 200 }),
    );
  });
});
