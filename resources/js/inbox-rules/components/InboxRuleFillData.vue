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
      propScreenFields: {
        type: Array,
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
        const dataToUse = this.formData;
        this.propScreenFields.forEach((field) => {

          const existingValue = _.get(dataToUse, field, null);
          let quickFillValue;

          if (existingValue) {
            // If the value exists in the task data, don't overwrite it
            quickFillValue = existingValue;
          } else {
            // use the value from the quick fill(
            quickFillValue = _.get(this.propInboxQuickFill, field, null);
          }
          // Set the value. This handles nested values using dot notation in 'field' string
          _.set(this.formData, field, quickFillValue);
        });
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
        this.sendEvent("fillData", this.inboxRuleData);
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