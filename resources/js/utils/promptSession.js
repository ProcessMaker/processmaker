/**
 * Prompt Session Utility for ProcessMaker AI Microservice
 * Provides functionality to manage prompt session IDs and nonces
 */

export default {
  data() {
    return {
      promptSessionId: localStorage.getItem("promptSessionId") || "",
      currentNonce: "",
    };
  },

  methods: {
    /**
     * Generate a new nonce and store it in localStorage
     * @returns {string} The generated nonce
     */
    getNonce() {
      const max = 999999999999999;
      const nonce = Math.floor(Math.random() * max);
      this.currentNonce = nonce.toString();
      localStorage.setItem("currentNonce", this.currentNonce);
      return this.currentNonce;
    },

    /**
     * Get the current nonce from localStorage
     * @returns {string|null} The current nonce or null if not found
     */
    getCurrentNonce() {
      return localStorage.getItem("currentNonce");
    },

    /**
     * Get or create a prompt session ID
     * @returns {Promise<string>} The prompt session ID
     */
    async getPromptSession() {
      const url = "/package-ai/getPromptSessionHistory";
      let params = {
        server: window.location.host,
      };

      // Reset session ID if it starts with "ss" (invalid format)
      if (this.promptSessionId && this.promptSessionId.startsWith("ss")) {
        this.promptSessionId = "";
      }

      // Use existing session ID if available
      if (this.promptSessionId && this.promptSessionId !== null && this.promptSessionId !== "") {
        params = { promptSessionId: this.promptSessionId };
      }

      try {
        const response = await ProcessMaker.apiClient.post(url, params);
        this.promptSessionId = response.data.promptSessionId;
        localStorage.setItem("promptSessionId", this.promptSessionId);
        return this.promptSessionId;
      } catch (error) {
        const errorMsg = error.response?.data?.message || error.message;

        if (error.response?.status === 404) {
          // Session not found, clear it and try again
          localStorage.removeItem("promptSessionId");
          this.promptSessionId = "";
          return this.getPromptSession();
        }
        // eslint-disable-next-line no-console
        console.error("Error getting prompt session:", errorMsg);
        throw error;
      }
    },

    /**
     * Clear the current prompt session
     */
    clearPromptSession() {
      this.promptSessionId = "";
      localStorage.removeItem("promptSessionId");
      localStorage.removeItem("currentNonce");
    },

    /**
     * Check if a prompt session exists
     * @returns {boolean} True if session exists
     */
    hasPromptSession() {
      return this.promptSessionId && this.promptSessionId !== "";
    },
  },
};

/**
 * Standalone utility functions that can be used without Vue component
 */
export const promptSessionUtils = {
  /**
   * Get prompt session ID from localStorage
   * @returns {string} The prompt session ID
   */
  getPromptSessionId() {
    return localStorage.getItem("promptSessionId") || "";
  },

  /**
   * Set prompt session ID in localStorage
   * @param {string} sessionId - The session ID to store
   */
  setPromptSessionId(sessionId) {
    localStorage.setItem("promptSessionId", sessionId);
  },

  /**
   * Clear prompt session from localStorage
   */
  clearPromptSession() {
    localStorage.removeItem("promptSessionId");
    localStorage.removeItem("currentNonce");
  },

  /**
   * Generate a new nonce
   * @returns {string} The generated nonce
   */
  generateNonce() {
    const max = 999999999999999;
    const nonce = Math.floor(Math.random() * max);
    const nonceString = nonce.toString();
    localStorage.setItem("currentNonce", nonceString);
    return nonceString;
  },

  /**
   * Get current nonce from localStorage
   * @returns {string|null} The current nonce
   */
  getCurrentNonce() {
    return localStorage.getItem("currentNonce");
  },

  /**
   * Make API call to get prompt session (standalone version)
   * @returns {Promise<string>} The prompt session ID
   */
  async getPromptSession() {
    const url = "/package-ai/getPromptSessionHistory";
    let params = {
      server: window.location.host,
    };

    const currentSessionId = this.getPromptSessionId();

    // Reset session ID if it starts with "ss" (invalid format)
    if (currentSessionId && currentSessionId.startsWith("ss")) {
      this.clearPromptSession();
    }

    // Use existing session ID if available
    if (currentSessionId && currentSessionId !== "") {
      params = { promptSessionId: currentSessionId };
    }

    try {
      const response = await ProcessMaker.apiClient.post(url, params);
      const sessionId = response.data.promptSessionId;
      this.setPromptSessionId(sessionId);
      return sessionId;
    } catch (error) {
      const errorMsg = error.response?.data?.message || error.message;

      if (error.response?.status === 404) {
        // Session not found, clear it and try again
        this.clearPromptSession();
        return this.getPromptSession();
      }
      // eslint-disable-next-line no-console
      console.error("Error getting prompt session:", errorMsg);
      throw error;
    }
  },
};
