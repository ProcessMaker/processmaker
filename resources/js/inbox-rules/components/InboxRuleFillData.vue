<template>
  <div>
    <b-embed :src="linkTasks" @load="fillWithData()" id="formPreview" /> 
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
  },
  data() {
    return {
      formData: {},
    }
  },
  computed: {
    linkTasks() {
      return `/tasks/${this.taskId}/edit/preview?dispatchSubmit=1`;
    }
  },
  watch: {
    formData() {
      this.$emit("data", this.formData);
    },
  },
  mounted() {
    window.addEventListener('dataUpdated', (event) => {
      this.formData = event.detail;
    });
    window.addEventListener('formSubmit', (event) => {
      console.log("Got form submit", event.detail)
    });
  },
  methods: {
    reload() {
      document.getElementById('formPreview').contentWindow.location.reload();
    },
    fillWithData() {
      const data = this.inboxRuleData || {};
      this.sendEvent("fillData", data);
    },
    sendEvent(name, data)
    {
      const event = new CustomEvent(name, {
        detail: data
      });

      document
        .getElementById("formPreview")
        .contentWindow.dispatchEvent(event);
    }
  }
}
</script>