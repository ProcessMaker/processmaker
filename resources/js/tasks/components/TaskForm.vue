<template>
  <vue-form-renderer @submit="submit" v-model="formData" :config="screen" :computed="computed" :custom-css="customCss" />
</template>

<script>
import { VueFormRenderer } from '@processmaker/spark-screen-builder';
import { renderer } from "@processmaker/spark-screen-builder";
import VueFormElements from "@processmaker/vue-form-elements";
import FileUpload from "../../processes/screen-builder/components/form/file-upload";
import FileDownload from "../../processes/screen-builder/components/file-download";

Vue.use(VueFormElements);

export default {
  components: {
    VueFormRenderer
  },
  props: ["processId", "instanceId", "tokenId", "screen", "data", "computed", "customCss"],
  data() {
    return {
      formData: this.data
    };
  },
  mounted() {},
  methods: {
    displayErrors(errors) {
      const messages = [];
      Object.keys(errors).forEach((key) => {
        errors[key].forEach((message) => {
          messages.push(message);
        });
      });
      return messages.join("\n");
    },
    submit() {
      let message = this.$t('Task Completed Successfully');
      ProcessMaker.apiClient
        .put("tasks/" + this.tokenId, {status:"COMPLETED", data: this.formData})
        .then(function() {
          window.ProcessMaker.alert(message, 'success', 60, true);
          document.location.href = "/tasks";
        })
        .catch(error => {
          let message = error.response.data && error.response.data.errors && this.displayErrors(error.response.data.errors) || error && error.message;
          ProcessMaker.alert(error.response.data.message, 'danger');
          ProcessMaker.alert(message, 'danger');
        });
    },
    update(data) {
      this.formData = data;
    }
  }
};
</script>
