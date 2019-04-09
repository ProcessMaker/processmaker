<template>
  <div class="modal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">{{ title }}</h5>
              </div>
              
              <div class="modal-body mb-1">
                <p><span v-html="message"></span></p>
              </div>

              <div class="progress ml-4 mr-4 mb-3">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100" style="width: 15%"></div>
              </div>

              <div class="modal-footer">
                  <a role="button" class="btn btn-secondary ml-2" href="/logout" :disabled="disabled">{{('LogOut')}}</a>
                  <button type="button" class="btn btn-secondary ml-2" @click="onLogin" :disabled="disabled">{{('Stay Connected')}}</button>
              </div>
          </div>
      </div>
  </div>
</template>


<script>

    export default {
        props: ["title", "message"],
        data() {
            return {
              errors: {},
              disabled: false
            }
        },
        methods: {
            onClose() {
                this.$emit('close');
            },
            onLogin() {
                this.disabled = true;
                ProcessMaker.apiClient
                  .post("/login", {
                    username: this.username,
                    password: this.password
                  },
                  {
                    baseURL: ''
                  })
                  .then(() => {
                    this.disabled = false;
                    ProcessMaker.AccountTimeoutWorker.postMessage({method: 'start', data: {timeout: ProcessMaker.AccountTimeoutLength}});
                    this.onClose();
                  })
                  .catch(error => {
                    this.disabled = false;
                    this.errors = error.response.data.errors;
                  });
            }
        },
        mounted() {
            this.$emit("show");
        }
    }
</script>

<style scoped>

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        bottom: 0;
        right: 0;
        background: rgba(0, 0, 0, .5);
        z-index: 1060;
        display: flex;
        min-width: 30%;
    }

    .modal-dialog {
        min-width: 400px;
        top: 20%;
    }

</style>