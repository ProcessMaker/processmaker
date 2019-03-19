<template>
  <div class="modal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title">{{ title }}</h5>
              </div>
              <div class="modal-body mb-1">
                <p><span v-html="message"></span></p>
                <div class="form-group">
                  <label for="username">Username</label>
                  <div>
                    <input id="username" type="text" class="form-control" :class="{ 'is-invalid': errors.username }" name="username" v-model="username" required>
                    <span class="invalid-feedback" v-if="errors.username">
                      <strong>{{errors.username[0]}}</strong>
                    </span>
                  </div>
                </div>
                <div class="form-group">
                  <label for="password">Password</label>
                  <div class="">
                    <input id="password" type="password" class="form-control" :class="{ 'is-invalid': errors.password }" name="password" v-model="password" required>
                    <span class="invalid-feedback" v-if="errors.password">
                      <strong>{{errors.password[0]}}</strong>
                    </span>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                  <button type="button" class="float-right btn btn-secondary ml-2" @click="onLogin" :disabled="disabled">Log In</button>
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
              username: null,
              password: null,
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