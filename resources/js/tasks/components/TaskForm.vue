<template>
  <vue-form-renderer @submit="submit" v-model="formData" :config="screen" :computed="computed" :custom-css="customCss" />
</template>

<script>
import {
  VueFormRenderer,
  OptionsList,
  PageSelect,
  FormMultiColumn,
  MultiColumn,
  FormText,
  renderer,
  FormRecordList
} from '@processmaker/spark-screen-builder';
const FormButton = renderer.FormButton;

import FileUpload from "../../processes/screen-builder/components/form/file-upload";
import FileDownload from "../../processes/screen-builder/components/file-download";

import {
  FormInput,
  FormSelect,
  FormTextArea,
  FormCheckbox,
  FormRadioButtonGroup,
  FormDatePicker
} from "@processmaker/vue-form-elements/src/components";
Vue.component("FormInput", FormInput);
Vue.component("FileUpload", FileUpload);
Vue.component("FileDownload", FileDownload);
Vue.component("FormSelect", FormSelect);
Vue.component("OptionsList", OptionsList);
Vue.component("FormCheckbox", FormCheckbox);
Vue.component("FormRadioButtonGroup", FormRadioButtonGroup);
Vue.component("FormTextArea", FormTextArea);
Vue.component("FormText", FormText);
Vue.component("FormButton", FormButton);
Vue.component("PageSelect", PageSelect);
Vue.component("MultiColumn", MultiColumn);
Vue.component("FormMultiColumn", FormMultiColumn);
Vue.component("FormDatePicker", FormDatePicker);
Vue.component("FormRecordList", FormRecordList);

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
    submit() {
      let message = this.$t('Task Completed Successfully');
      ProcessMaker.apiClient
        .put("tasks/" + this.tokenId, {status:"COMPLETED", data: this.formData})
        .then(function() {
          window.ProcessMaker.alert(message, 'success', 60, true);
          document.location.href = "/tasks";
        });
    },
    update(data) {
      this.formData = data;
    }
  }
};
</script>

<style lang="scss" scoped>
</style>
