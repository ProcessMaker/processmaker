<template>
  <b-modal
    id="sessionModal"
    ref="sessionModal"
    :title="title"
    footer-class="pm-modal-footer"
    no-close-on-backdrop
    centered
    no-close-button
  >
    <template #modal-header>
      <h5>{{ title }}</h5>
    </template>
    <div v-if="!isProcessing">
      <span v-html="message" />
      <div class="progress">
        <div
          class="progress-bar progress-bar-striped"
          role="progressbar"
          :style="{width: percentage + '%'}"
        >
          <span
            align="left"
            class="pl-2"
          >{{ moment().startOf('day').seconds(time).format('mm:ss') }}</span>
        </div>
      </div>
    </div>
    <div
      v-else
      class="d-flex align-items-center justify-content-center py-3"
    >
      <output
        class="spinner-border spinner-border-sm mr-2"
        aria-live="polite"
      />
      <span>{{ ("Processing...") }}</span>
    </div>
    <template #modal-footer>
      <button
        v-if="!isProcessing"
        type="button"
        class="btn btn-outline-secondary ml-2"
        :disabled="isBusy"
        @click="logoutNow"
      >
        {{ ('LogOut') }}
      </button>
      <button
        v-if="!isProcessing"
        type="button"
        class="btn btn-secondary ml-2"
        :disabled="isBusy"
        @click="keepAlive"
      >
        {{ ('Stay Connected') }}
      </button>
    </template>
  </b-modal>
</template>

<script>

export default {
  props: ["title", "message", "time", "warnSeconds", "shown", "isRenewing"],
  data() {
    return {
      errors: {},
      disabled: false,
      localRenewing: false,
    };
  },
  computed: {
    isRenewingEffective() {
      return this.localRenewing || this.isRenewing;
    },
    isProcessing() {
      return this.isRenewingEffective;
    },
    isBusy() {
      return this.disabled || this.isRenewingEffective;
    },
    percentage() {
      if (this.time === "" || this.warnSeconds === "") {
        return 0;
      }
      return Math.round((this.time / this.warnSeconds) * 100);
    },
  },
  watch: {
    shown(value) {
      if (value) {
        this.resetProcessingState();
      }
      if (value) {
        this.$refs.sessionModal.show();
      } else {
        this.$refs.sessionModal.hide();
      }
    },
  },
  mounted() {
    this.$emit("show");
  },
  methods: {
    resetProcessingState() {
      this.localRenewing = false;
      this.disabled = false;
      this.errors = {};
    },
    onClose() {
      this.$emit("close");
    },
    keepAlive() {
      this.disabled = true;
      this.setRenewingState(true);

      ProcessMaker.apiClient
        .post("/keep-alive", {}, { baseURL: "" })
        .then(() => {
          this.disabled = false;
          this.setRenewingState(false);
          const timeout = window.ProcessMaker.AccountTimeoutLength;
          if (window.ProcessMaker.sessionSync?.setSessionState) {
            window.ProcessMaker.sessionSync.setSessionState(timeout);
          }
          if (window.ProcessMaker.sessionSync?.clearWarningState) {
            window.ProcessMaker.sessionSync.clearWarningState();
          }
          if (window.ProcessMaker.sessionSync?.broadcast) {
            window.ProcessMaker.sessionSync.broadcast("renewed", { timeout });
          }
          // If reponse is correct, the timer is started again.
          if (window.ProcessMaker.sessionSync?.isLeader?.() && typeof window.ProcessMaker.AccountTimeoutWorker !== "undefined") {
            window.ProcessMaker.AccountTimeoutWorker.postMessage({
              method: "start",
              data: {
                timeout,
                warnSeconds: window.ProcessMaker.AccountTimeoutWarnSeconds,
                enabled: window.ProcessMaker.AccountTimeoutEnabled,
              },
            });
          }
          this.onClose();
        })
        .catch((error) => {
          const status = error?.response?.status;
          if (status === 401 || status === 419) {
            // Session expired server-side; broadcast and redirect.
            this.setRenewingState(false);
            this.broadcastExpired();
            window.location.href = "/logout";
            return;
          }
          this.disabled = false;
          this.setRenewingState(false);
          this.errors = error.response.data.errors;
        });
    },
    setRenewingState(isRenewing) {
      this.localRenewing = isRenewing;
      // Broadcast renewal status so other tabs show the spinner.
      if (window.ProcessMaker.sessionSync?.broadcast) {
        window.ProcessMaker.sessionSync.broadcast("renewing", { isRenewing });
      }
      if (window.ProcessMaker.sessionSync?.setRenewingState) {
        window.ProcessMaker.sessionSync.setRenewingState(isRenewing);
      }
    },
    broadcastExpired() {
      // Sync timeout state across tabs.
      if (window.ProcessMaker.sessionSync?.clearWarningState) {
        window.ProcessMaker.sessionSync.clearWarningState();
      }
      if (window.ProcessMaker.sessionSync?.broadcast) {
        window.ProcessMaker.sessionSync.broadcast("expired");
      }
    },
    broadcastLogout() {
      // Sync manual logout state across tabs.
      if (window.ProcessMaker.sessionSync?.clearWarningState) {
        window.ProcessMaker.sessionSync.clearWarningState();
      }
      if (window.ProcessMaker.sessionSync?.broadcast) {
        window.ProcessMaker.sessionSync.broadcast("logout");
      }
    },
    logoutNow() {
      // Ensure other tabs close warning before redirect.
      this.disabled = true;
      this.setRenewingState(true);
      this.broadcastLogout();
      window.location.href = "/logout";
    },
  },
};
</script>

<style scoped>

    .modal {
        position: fixed;
        background: rgba(0, 0, 0, .5);
        z-index: 1060;
        display: flex;
    }

</style>
