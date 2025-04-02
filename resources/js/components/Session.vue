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
    <template #modal-header="{ close }">
      <h5>{{ title }}</h5>
    </template>
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
    <template #modal-footer>
      <a
        role="button"
        class="btn btn-outline-secondary ml-2"
        href="/logout"
        :disabled="disabled"
      >{{ ('LogOut') }}</a>
      <button
        type="button"
        class="btn btn-secondary ml-2"
        :disabled="disabled"
        @click="keepAlive"
      >
        {{ ('Stay Connected') }}
      </button>
    </template>
  </b-modal>
</template>

<script>

export default {
  props: ["title", "message", "time", "warnSeconds", "shown"],
  data() {
    return {
      errors: {},
      disabled: false,
    };
  },
  computed: {
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
    onClose() {
      this.$emit("close");
    },
    keepAlive() {
      this.disabled = true;

      ProcessMaker.apiClient
        .post("/keep-alive", {}, { baseURL: "" })
        .then(() => {
          this.disabled = false;
          // If reponse is correct, the timer is started again.
          if (typeof window.ProcessMaker.AccountTimeoutWorker !== 'undefined') {
            window.ProcessMaker.AccountTimeoutWorker.postMessage({
              method: "start",
              data: {
                timeout: window.ProcessMaker.AccountTimeoutLength,
                warnSeconds: window.ProcessMaker.AccountTimeoutWarnSeconds,
                enabled: window.ProcessMaker.AccountTimeoutEnabled,
              },
            });
          }
          this.onClose();
        })
        .catch((error) => {
          this.disabled = false;
          this.errors = error.response.data.errors;
        });
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
