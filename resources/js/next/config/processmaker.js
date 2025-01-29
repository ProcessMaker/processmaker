import axios from "axios";
import { setGlobalPMVariables, getGlobalPMVariable } from "../globalVariables";

export default () => {
  const token = document.head.querySelector("meta[name=\"csrf-token\"]");
  const EventBus = getGlobalPMVariable("EventBus");

  // Setup api versions
  const apiVersionConfig = [
    { version: "1.0", baseURL: "/api/1.0/" },
    { version: "1.1", baseURL: "/api/1.1/" },
  ];

  // Set the default API timeout
  let apiTimeout = 5000;

  /**
   * Create a axios instance which any vue component can bring in to call
   * REST api endpoints through oauth authentication
   */

  const apiClient = axios;

  apiClient.defaults.headers.common["X-Requested-With"] = "XMLHttpRequest";

  apiClient.defaults.baseURL = apiVersionConfig[0].baseURL;

  apiClient.interceptors.request.use((config) => {
    if (typeof config.url !== "string" || !config.url) {
      throw new Error("Invalid URL in the request configuration");
    }

    apiVersionConfig.forEach(({ version, baseURL }) => {
      const versionPrefix = `/api/${version}/`;
      if (config.url.startsWith(versionPrefix)) {
        // eslint-disable-next-line no-param-reassign
        config.baseURL = baseURL;
        // eslint-disable-next-line no-param-reassign
        config.url = config.url.replace(versionPrefix, "");
      }
    });

    return config;
  });

  // flags print forms
  apiClient.requestCount = 0;
  apiClient.requestCountFlag = false;
  apiClient.interceptors.request.use((request) => {
    // flags print forms
    if (apiClient.requestCountFlag) {
      apiClient.requestCount += 1;
    }

    EventBus.$emit("api-client-loading", request);
    return request;
  });

  apiClient.interceptors.response.use((response) => {
    // TODO: this could be used to show a default "created/upated/deleted resource" alert
    // response.config.method (PUT, POST, DELETE)
    // response.config.url (extract resource name)
    EventBus.$emit("api-client-done", response);

    if (apiClient.requestCountFlag && apiClient.requestCount > 0) {
      apiClient.requestCount -= 1;
    }
    return response;
  }, (error) => {
    // Set in your .catch to false to not show the alert inside window.ProcessMaker.apiClient
    if (!error?.response?.showAlert) {
      return Promise.reject(error);
    }

    if (error.code && error.code === "ERR_CANCELED") {
      return Promise.reject(error);
    }
    EventBus.$emit("api-client-error", error);
    if (error.response && error.response.status && error.response.status === 401) {
      // stop 401 error consuming endpoints with data-sources
      const { url } = error.config;
      if (url.includes("/data_sources/")) {
        if (url.includes("requests/") || url.includes("/test")) {
          throw error;
        }
      }
      window.location = "/login";
    } else {
      if (_.has(error, "config.url") && !error.config.url.match("/debug")) {
        apiClient.post("/debug", {
          name: "Javascript ProcessMaker.apiClient Error",
          message: JSON.stringify({
            message: error.message,
            code: error.code,
            config: error.config,
          }),
        });
      }
      return Promise.reject(error);
    }
  });

  /**
   * Next we will register the CSRF Token as a common header with Axios so that
   * all outgoing HTTP requests automatically have it attached. This is just
   * a simple convenience so we don't have to attach every token manually.
   */

  if (token) {
    apiClient.defaults.headers.common["X-CSRF-TOKEN"] = token.content;
  } else {
    console.error("CSRF token not found: https://laravel.com/docs/csrf#csrf-x-csrf-token");
  }

  if (window.Processmaker && window.Processmaker.apiTimeout !== undefined) {
    apiTimeout = window.Processmaker.apiTimeout;
  }

  apiClient.defaults.timeout = apiTimeout;

  // Display any uncaught promise rejections from axios in the Process Maker alert box
  window.addEventListener("unhandledrejection", (event) => {
    const error = event.reason;
    if (error.config && error.config._defaultErrorShown) {
      // Already handeled
      event.preventDefault(); // stops the unhandled rejection error
    } else if (error.response && error.response.data && error.response.data.message) {
      if (!(error.code && error.code === "ECONNABORTED")) {
        window.ProcessMaker.alert(error.response.data.message, "danger");
      }
    } else if (error.message) {
      if (!(error.code && error.code === "ECONNABORTED")) {
        window.ProcessMaker.alert(error.message, "danger");
      }
    }
  });

  setGlobalPMVariables({
    apiClient,
  });
};
