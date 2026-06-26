<template>
  <div>
    <div
      v-if="shown"
      class="session-timeout-overlay"
      role="presentation"
    >
      <dialog
        ref="sessionDialog"
        class="session-timeout-dialog"
        :aria-label="title"
        open
        tabindex="-1"
      >
        <header class="session-timeout-header">
          <h5>{{ title }}</h5>
        </header>
        <div class="session-timeout-body">
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
        </div>
        <footer class="pm-modal-footer session-timeout-footer">
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
        </footer>
      </dialog>
    </div>
  </div>
</template>

<script>

export default {
  props: ["title", "message", "time", "warnSeconds", "shown", "isRenewing"],
  data() {
    return {
      errors: {},
      disabled: false,
      localRenewing: false,
      originalParent: null,
      originalNextSibling: null,
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
        this.lockBodyScroll();
        this.focusDialog();
      } else {
        this.unlockBodyScroll();
      }
    },
  },
  mounted() {
    this.mountOverlayInBody();
    if (this.shown) {
      this.lockBodyScroll();
      this.focusDialog();
    }
    this.$emit("show");
  },
  beforeDestroy() {
    this.unlockBodyScroll();
    this.restoreOverlayParent();
  },
  methods: {
    mountOverlayInBody() {
      if (!document?.body || this.$el.parentNode === document.body) {
        return;
      }

      this.originalParent = this.$el.parentNode;
      this.originalNextSibling = this.$el.nextSibling;
      document.body.appendChild(this.$el);
    },
    restoreOverlayParent() {
      if (!this.originalParent || !this.$el.parentNode) {
        return;
      }

      this.$el.remove();
      this.originalParent.insertBefore(this.$el, this.originalNextSibling);
    },
    lockBodyScroll() {
      document?.body?.classList.add("session-timeout-open");
    },
    unlockBodyScroll() {
      document?.body?.classList.remove("session-timeout-open");
    },
    focusDialog() {
      this.$nextTick(() => {
        globalThis.setTimeout(() => {
          const firstButton = this.$refs.sessionDialog?.querySelector("button:not([disabled])");
          if (firstButton) {
            firstButton.focus();
            return;
          }

          this.$refs.sessionDialog?.focus();
        }, 50);
      });
    },
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
        .then((response) => {
          const { token } = response.data || {};

          this.disabled = false;
          this.setRenewingState(false);

          if (token && ProcessMaker.applyCsrfToken) {
            ProcessMaker.applyCsrfToken(token);
          }

          if (token) {
            this.$emit("xsrf-updated", { token });
          }

          const timeout = window.ProcessMaker.AccountTimeoutLength;
          if (ProcessMaker.sessionSync?.renewSession) {
            ProcessMaker.sessionSync.renewSession(timeout);
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
          this.errors = error?.response?.data?.errors || {};
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

<style>
body.session-timeout-open {
  overflow: hidden;
}

.session-timeout-overlay {
  align-items: center;
  background: rgba(0, 0, 0, 0.5);
  bottom: 0;
  display: flex;
  justify-content: center;
  left: 0;
  overflow-x: hidden;
  overflow-y: auto;
  padding: 1.75rem;
  position: fixed;
  right: 0;
  top: 0;
  z-index: 2147483647;
}

.session-timeout-dialog {
  background: #fff;
  border-radius: 0.3rem;
  border: 0;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.5);
  display: flex;
  flex-direction: column;
  margin: 0;
  max-width: 500px;
  outline: 0;
  padding: 0;
  position: relative;
  width: 100%;
}

.session-timeout-header {
  align-items: flex-start;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  padding: 1rem;
}

.session-timeout-header h5 {
  line-height: 1.5;
  margin: 0;
}

.session-timeout-body {
  flex: 1 1 auto;
  padding: 1rem;
  position: relative;
}

.session-timeout-footer {
  align-items: center;
  border-top: 1px solid #dee2e6;
  display: flex;
  justify-content: flex-end;
  padding: 0.75rem;
}
</style>
