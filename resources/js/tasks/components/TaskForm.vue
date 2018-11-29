<template>
  <vue-form-renderer @submit="submit" v-model="formData" :config="screen"/>
</template>

<script>
  import VueFormRenderer from "@processmaker/vue-form-builder/src/components/vue-form-renderer";

  import OptionsList from "@processmaker/vue-form-builder/src/components/inspector/options-list";
  import PageSelect from "@processmaker/vue-form-builder/src/components/inspector/page-select";
  import FormMultiColumn from "@processmaker/vue-form-builder/src/components/renderer/form-multi-column";
  import MultiColumn from "@processmaker/vue-form-builder/src/components/editor/multi-column";
  import FormText from "@processmaker/vue-form-builder/src/components/renderer/form-text";
  import FormButton from "@processmaker/vue-form-builder/src/components/renderer/form-button";
  import FormRecordList from '@processmaker/vue-form-builder/src/components/renderer/form-record-list'
  import FileUpload from "../../processes/screen-builder/components/form/file-upload";
  import {
    FormInput,
    FormSelect,
    FormTextArea,
    FormCheckbox,
    FormRadioButtonGroup,
    FormDatePicker
  } from "@processmaker/vue-form-elements/src/components";
  Vue.component('FormInput',FormInput);
  Vue.component('FileUpload',FileUpload);
  Vue.component('FormSelect',FormSelect);
  Vue.component('OptionsList',OptionsList);
  Vue.component('FormCheckbox',FormCheckbox);
  Vue.component('FormRadioButtonGroup',FormRadioButtonGroup);
  Vue.component('FormTextArea',FormTextArea);
  Vue.component('FormText',FormText);
  Vue.component('FormButton',FormButton);
  Vue.component('PageSelect',PageSelect);
  Vue.component('MultiColumn',MultiColumn);
  Vue.component('FormMultiColumn',FormMultiColumn);
  Vue.component('FormDatePicker',FormDatePicker);
  Vue.component('FormRecordList',FormRecordList);

export default {
  components: {
    VueFormRenderer
  },
  props: [
    'processId',
    'instanceId',
    'tokenId',
    'screen',
    'data'
  ],
  data() {
    return {
      formData: this.data
    };
  },
  mounted() {
  },
  methods: {
    submit() {
      var self = this;
      ProcessMaker.apiClient.put(
          'tasks/' + this.tokenId +
          '?status=COMPLETED',
          this.formData)
        .then(function() {
          document.location.href = '/tasks';
        });
    },
    update(data) {
      this.formData = data;
    }
  }
}
</script>

<style lang="scss" scoped>
</style>
