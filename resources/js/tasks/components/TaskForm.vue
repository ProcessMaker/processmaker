<template>
  <vue-form-renderer @submit="submit" v-model="formData" :config="screen" :computed="computed" :custom-css="customCss" :watchers="watchers" @update="onUpdate" />
</template>

<script>
import { VueFormRenderer } from '@processmaker/screen-builder';
import '@processmaker/screen-builder/dist/vue-form-builder.css';
import ProcessRequestChannel from './ProcessRequestChannel';

export default {
  components: {
    VueFormRenderer
  },
  mixins: [ProcessRequestChannel],
  props: ["processId", "instanceId", "tokenId", "screen", "data", "computed", "customCss", "watchers"],
  data() {
    return {
      disabled: false,
      formData: this.data
    };
  },
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
      //single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      let message = this.$t('Task Completed Successfully');
      ProcessMaker.apiClient
        .put("tasks/" + this.tokenId, {status:"COMPLETED", data: this.formData})
        .then(() => {
          window.ProcessMaker.alert(message, 'success', 5, true);
        })
        .catch(error => {
          this.disabled = false;
          // If there are errors, the user will be redirected to the request page
          // to view error details. This is done in loadTask in edit.blade.php
        });
    },
    onUpdate(data) {
      ProcessMaker.EventBus.$emit('form-data-updated', data);
    },
    update(data) {
      this.formData = data;
    }
  },
  watch: {
    data: {
      deep: true,
      handler(data) {
        this.formData = data;
      }
    }
  }
};
</script>
