<template>
  <div>
    <b-embed
      ref="preview"
      :src="linkTasks"
      :disable-interstitial="true"
      :event-parent-id="_uid"
      @load="loaded()"
    />
  </div>
</template>

<script>
export default {
  props: {
    taskId: {
      type: Number,
      default: null,
    },
    inboxRuleData: {
      type: Object,
      default: null,
    },
    propInboxQuickFill: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      formData: {},
      lastClickedButton: null,
    };
  },
  computed: {
    linkTasks() {
      return `/tasks/${this.taskId}/edit/preview?dispatchSubmit=1&alwaysAllowEditing=1&disableInterstitial=1`;
    },
    iframeContentWindow() {
      return this.$refs.preview.firstChild.contentWindow;
    },
  },
  watch: {
    formData() {
      this.$emit("data", this.formData);
    },
    propInboxQuickFill() {
      this.formData = _.merge({}, this.formData, this.propInboxQuickFill);
      this.iframeContentWindow.location.reload();
    },
  },
  mounted() {
    this.receiveEvent("dataUpdated", (data) => {
      this.formData = data;
    });
    this.receiveEvent("formSubmit", (data) => {
      // Use buttonInfo from event if it's a valid object, otherwise use lastClickedButton
      let submitData = null;
      if (data && typeof data === "object" && !Array.isArray(data)) {
        submitData = data;
      } else if (this.lastClickedButton && this.lastClickedButton.label) {
        // Fallback: use button info captured from iframe click listener
        submitData = {
          name: this.lastClickedButton.name,
          label: this.lastClickedButton.label,
          value: this.lastClickedButton.value,
        };
      }
      this.$emit("submit", submitData);
    });
    this.receiveEvent("taskReady", () => {
      this.sendEvent("fillDataOverwriteExistingFields", this.inboxRuleData);
      // Setup click listener for submit buttons in iframe after task is ready
      this.$nextTick(() => {
        this.setupButtonClickListener();
      });
    });
  },
  methods: {
    eraseData() {
      this.sendEvent("eraseData", true);
    },
    reload() {
      this.formData = {};
      this.iframeContentWindow.location.reload();
    },
    loaded() {
      // eslint-disable-next-line no-underscore-dangle
      this.iframeContentWindow.event_parent_id = this._uid;
      this.sendEvent("sendValidateForm", false);
    },
    sendEvent(name, data) {
      const event = new CustomEvent(name, {
        detail: data,
      });
      this.iframeContentWindow.dispatchEvent(event);
    },
    receiveEvent(name, callback) {
      window.addEventListener(name, (event) => {
        // eslint-disable-next-line no-underscore-dangle
        if (event.detail.event_parent_id !== this._uid) {
          return;
        }
        callback(event.detail.data);
      });
    },
    setupButtonClickListener() {
      try {
        const iframeDoc = this.iframeContentWindow.document;
        // Listen for clicks on submit buttons in the iframe
        iframeDoc.addEventListener("click", (event) => {
          const button = event.target.closest("button");
          if (button) {
            // Check if this is a submit button (has btn-primary class or is inside form-group)
            const isSubmitButton = button.classList.contains("btn-primary")
              || button.classList.contains("btn-secondary")
              || button.closest(".form-group");
            if (isSubmitButton) {
              this.lastClickedButton = {
                name: button.getAttribute("name") || null,
                label: button.textContent?.trim() || null,
                value: button.value || null,
              };
            }
          }
        }, true);
      } catch (e) {
        // Cross-origin restrictions may prevent access to iframe content
        // eslint-disable-next-line no-console
        console.warn("Could not setup button click listener in iframe:", e.message);
      }
    },
  },
};
</script>
