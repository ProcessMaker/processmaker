export default {
  data() {
    return {
      debounceTimeout: null,
    };
  },
  methods: {
    async handleAutosave(force = false, generatingAssets = false) {
      if (this.isVersionsInstalled === false) {
        return;
      }

      if (typeof this.autosaveApiCall !== "function") {
        return;
      }

      if (force) {
        this.autosaveApiCall(generatingAssets);
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
