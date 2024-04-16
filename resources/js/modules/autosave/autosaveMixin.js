export default {
  data() {
    return {
      debounceTimeout: null,
    };
  },
  methods: {
    async handleAutosave(force = false, generatingAssets = false, redirectUrl = null, nodeId = null) {
      if (this.isVersionsInstalled === false) {
        return;
      }

      if (typeof this.autosaveApiCall !== "function") {
        return;
      }

      if (force) {
        this.autosaveApiCall(generatingAssets, redirectUrl, nodeId);
      } else {
        if (this.debounceTimeout) {
          clearTimeout(this.debounceTimeout);
        }

        this.debounceTimeout = setTimeout(() => {
          this.autosaveApiCall(generatingAssets);
        }, this.autoSaveDelay);
      }
    },
  },
};
