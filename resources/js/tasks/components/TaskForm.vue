<template>
  <vue-form-renderer @submit="submit" v-model="formData" :config="screen" :computed="computed" :custom-css="customCss" :watchers="watchers" @update="onUpdate" />
</template>

<script>
import { VueFormRenderer } from '@processmaker/screen-builder';
import '@processmaker/screen-builder/dist/vue-form-builder.css';

export default {
  components: {
    VueFormRenderer
  },
  props: ["processId", "instanceId", "tokenId", "screen", "data", "computed", "customCss", "watchers"],
  data() {
    return {
      disabled: false,
      formData: this.data,
      socketListeners: []
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
      this.initSocketListeners();
      let message = this.$t('Task Completed Successfully');
      ProcessMaker.apiClient
        .put("tasks/" + this.tokenId, {status:"COMPLETED", data: this.formData})
        .then(() => {
          this.disabled = false;
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
    },
    addSocketListener(channel, event, callback) {
      this.socketListeners.push({
        channel,
        event
      });
      window.Echo.private(channel).listen(
        event,
        callback
      );
    },
    obtainPayload(url) {
      return new Promise((resolve, reject) => {
        ProcessMaker.apiClient
          .get(url)
          .then(response => {
            resolve(response.data);
          }).catch(error => {
            // User does not have access to the resource. Ignore.
          });
      });
    },
    initSocketListeners() {
      const requestId = document.head.querySelector('meta[name="request-id"]').content;
      this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${requestId}`, ".ActivityAssigned", (data) => {
        if (data.payloadUrl) {
          this.obtainPayload(data.payloadUrl)
          .then(response => {
            this.$emit("activity-assigned", response);
          });        
        }
      });

      this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${requestId}`, ".ProcessCompleted", (data) => {
        if (data.payloadUrl) {
          this.obtainPayload(data.payloadUrl)
          .then(response => {
            this.$emit("process-completed", response);
          });
        }
      });

      this.addSocketListener(`ProcessMaker.Models.ProcessRequest.${requestId}`, ".ProcessUpdated", (data) => {
        if (data.payloadUrl) {
          this.obtainPayload(data.payloadUrl)
          .then(response => {
            if (data.event) {
              response.event = data.event;
            }
            this.$emit("process-updated", response);
          });
        }
      });
    }
  },
  watch: {
    data: {
      deep: true,
      handler(data) {
        this.formData = data;
      }
    },
    screen: {
      deep: true,
      handler() {
        this.disabled = false;
      }
    }
  },
  destroyed () {
    this.socketListeners.forEach((element) => {
      window.Echo.private(element.channel).stopListening(element.event);
    });
  }
};
</script>
