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
  mounted() {
    this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${this.instanceId}`, '.ActivityAssigned', (data) => {
      this.$emit('activity-assigned', data);
    });
    this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${this.instanceId}`, '.ProcessCompleted', (data) => {
      this.$emit('process-completed', data);
    });
    this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${this.instanceId}`, '.ProcessUpdated', (data) => {
      this.$emit('process-updated', data);
    });
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
          let message = error.response.data && error.response.data.errors && this.displayErrors(error.response.data.errors) || error && error.message;
          ProcessMaker.alert(error.response.data.message, 'danger');
          ProcessMaker.alert(message, 'danger');
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
