<template>
  <div>
    <b-embed
      :src="linkTasks"
      @load="loaded()"
      :disable-interstitial="true"
      ref="preview"
      :event-parent-id="_uid" /> 
  </div>
</template>


<script>
  export default {
    props: {
      taskId: {
        type: Number,
        default: null
      },
      inboxRuleData: {
        type: Object,
        default: null
      },
      propInboxQuickFill: {
        type: Object,
        default: null
      },
    },
    data() {
      return {
        formData: {}
      };
    },
    computed: {
      linkTasks() {
        return `/tasks/${this.taskId}/edit/preview?dispatchSubmit=1&alwaysAllowEditing=1&disableInterstitial=1`;
      },
      iframeContentWindow() {
        return this.$refs['preview'].firstChild.contentWindow;
      }
    },
    watch: {
      formData() {
        this.$emit("data", this.formData);
      },
      propInboxQuickFill() {
        this.formData = _.merge({}, this.formData, this.propInboxQuickFill);
        this.iframeContentWindow.location.reload();
      }
    },
    mounted() {
      this.receiveEvent('dataUpdated', (data) => {
        this.formData = data;
      });
      this.receiveEvent('formSubmit', (data) => {
        this.$emit("submit", data);
      });
      this.receiveEvent('taskReady', () => {
        this.sendEvent("fillDataOverwriteExistingFields", this.inboxRuleData);
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
        this.iframeContentWindow.event_parent_id = this._uid;
        this.sendEvent("sendValidateForm", false);
      },
      sendEvent(name, data) {
        const event = new CustomEvent(name, {
          detail: data
        });
        this.iframeContentWindow.dispatchEvent(event);
      },
      receiveEvent(name, callback) {
        window.addEventListener(name, (event) => {
          if (event.detail.event_parent_id !== this._uid) {
            return;
          }
          callback(event.detail.data);
        });
      },
    }
  }
</script>