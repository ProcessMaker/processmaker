import { cacheAdapterEnhancer } from "axios-extensions";
import * as LRUCacheModule from "lru-cache";

// Root install may hoist lru-cache@5 (default export only) or v10 (named LRUCache); screen-builder uses v10 API.
const LRUCache = LRUCacheModule.LRUCache ?? LRUCacheModule.default;

/**
 * Wraps the axios HTTP adapter with screen-builder GET caching (axios-extensions).
 * Axios 1.x sets defaults.adapter to adapter names (e.g. ['xhr','http','fetch']), not a
 * function; cacheAdapterEnhancer must receive the resolved adapter from getAdapter().
 *
 * @param {import("axios").AxiosInstance} apiClient
 * @param {{ cacheEnabled: boolean, cacheTimeout: number }} screen
 */
export default function attachScreenCacheAdapter(apiClient, screen) {
  const raw = apiClient.defaults.adapter;
  const baseAdapter = typeof raw === "function"
    ? raw
    : apiClient.getAdapter(raw);

  apiClient.defaults.adapter = cacheAdapterEnhancer(baseAdapter, {
    enabledByDefault: screen.cacheEnabled,
    cacheFlag: "useCache",
    defaultCache: new LRUCache({
      max: 100,
      ttl: screen.cacheTimeout,
      maxAge: screen.cacheTimeout,
    }),
  });
}
