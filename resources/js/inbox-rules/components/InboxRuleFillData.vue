<template>
  <div>
    <b-embed :src="linkTasks" @load="loaded()" ref="preview" /> 
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
      }
    },
    data() {
      return {
        formData: {}
      };
    },
    computed: {
      linkTasks() {
        return `/tasks/${this.taskId}/edit/preview?dispatchSubmit=1`;
      },
      iframeContentWindow() {
        return this.$refs['preview'].firstChild.contentWindow;
      }
    },
    watch: {
      formData() {
        this.$emit("data", this.formData);
      }
    },
    mounted() {
      this.receiveEvent('dataUpdated', (data) => {
        console.log('dataUpdated', data);
        this.formData = data;
      });
      this.receiveEvent('formSubmit', (data) => {
        this.$emit("submit", data);
      });
    },
    methods: {
      reload() {
        this.formData = {};
        this.iframeContentWindow.location.reload();
      },
      loaded() {
        console.log("loaded. Inbox rule data is ", JSON.stringify(this.inboxRuleData));
        this.iframeContentWindow.event_parent_id = this._uid;
        this.sendEvent("fillData", this.inboxRuleData);
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