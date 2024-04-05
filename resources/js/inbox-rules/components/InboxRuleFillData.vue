<template>
  <div>
    <b-embed :src="linkTasks" @load="loaded()" :disable-interstitial="true" ref="preview" /> 
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
      eraseInbox: {
        type: Boolean,
        default: false
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
      console.log("erase inbox: ", this.eraseInbox);
      this.receiveEvent('dataUpdated', (data) => {
        this.formData = data;
      });
      this.receiveEvent('formSubmit', (data) => {
        this.$emit("submit", data);
      });
      this.receiveEvent('readyForFillData', () => {
        this.sendEvent("fillData", this.inboxRuleData);
      });
    },
    methods: {
      clearScreen() {
        console.log("clear screen");
        this.sendEvent("eraseData", {});
        this.eraseInbox = true;
      },
      reload() {
        this.formData = {};
        this.iframeContentWindow.location.reload();
      },
      loaded() {
        this.iframeContentWindow.event_parent_id = this._uid;
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