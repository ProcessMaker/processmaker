<template>
  <div>
    <modal 
      id="set-password-modal" 
      :title="$t('Set Password')" 
      :subtitle="$t('This password will be required when importing the exported package/process.')"
      :ok-title="$t('Export')"
      :ok-disabled="disabled" 
      @ok.prevent="onExport" 
      @hidden="onClose" 
    >
      <template>
        <b-form-group>
          <div>
            <b-form-checkbox class="pt-2" v-model="passwordProtect" switch>
              Password Protect Export
            </b-form-checkbox>
          </div>
            <template v-if="passwordProtect === true">
              <div class="pt-3">
                <label for="set-password">Password</label>
                <b-input-group :state="errorState('password', errors)" :invalid-feedback="errorMessage('password', errors)">
                  <b-form-input
                    autofocus
                    ref="input"
                    id="set-password"
                    :type="type"
                    v-model="password"
                    autocomplete="off"
                    name="password"
                    class="form-control"
                    :state="errorState('password', errors)"
                  ></b-form-input>
                  <b-input-group-append>
                    <b-button :aria-label="$t('Toggle Show Password')" variant="link" @click="togglePassword" class="form-btn" :class="errors.password ? 'invalid' : ''">
                      <i class="fas text-secondary" :class="icon"></i>
                    </b-button>
                  </b-input-group-append>
                </b-input-group>
              </div>
              <div class="pt-3">
                <label for="confirm-set-password">Verify Password</label>
                <b-input-group :state="errorState('password', errors)" :invalid-feedback="errorMessage('password', errors)">
                  <b-form-input 
                    autofocus
                    id="confirm-set-password" 
                    :type="type"
                    v-model="confirmPassword"
                    autocomplete="off"
                    name="confirm-password"
                    class="form-control"
                  ></b-form-input>
                  <b-input-group-append>
                    <b-button :aria-label="$t('Toggle Show Password')" variant="link" @click="togglePassword" class="form-btn" :class="errors.password ? 'invalid' : ''">
                      <i class="fas text-secondary" :class="icon"></i>
                    </b-button>
                  </b-input-group-append>
                </b-input-group>
              </div>
            </template>
            <template v-else>
              <div class="pt-3">
                <label for="set-password">Password</label>
                <b-input-group>
                  <b-form-input
                    id="set-password"
                    name="set-password"
                    class="disabled-form"
                    disabled
                  ></b-form-input>
                </b-input-group>
              </div>
              <div class="pt-3">
                <label for="confirm-set-password">Verify Password</label>
                <b-input-group>
                  <b-form-input
                    id="confirm-set-password"
                    name="confirm-password"
                    class="disabled-form"
                    disabled
                  ></b-form-input>
                </b-input-group>  
              </div>
            </template>
        </b-form-group>
      </template>
    </modal>
  </div>
</template>

<script>
import { FormErrorsMixin, Modal } from "SharedComponents";

export default {
  components: { Modal },
  props: ["processId"],
  mixins: [ FormErrorsMixin ],
  data() {
      return {
        passwordProtect: true,
        disabled: false,
        password: '',
        confirmPassword: '',
        type: 'password',
        errors: {
        'password': null,
        }
      }
  },
  computed: {
    icon() {
    if (this.type == 'password') {
      return 'fa-solid fa-eye';
    } else {
      return 'fa-solid fa-eye-slash';
      }
    },
  },
  watch: {
    password() {
      this.disabled = this.password ? false : true;
      this.resetErrors();
    }
  },
  methods: { 
    resetFormData() {
      this.formData = Object.assign({}, {
        password: null,
      });
    },
    resetErrors() {
      this.errors = Object.assign({}, {
        password: null,
      });
    },
    onClose() {
          this.resetFormData();
          this.resetErrors();
    },
    onExport() {
          ProcessMaker.apiClient.post('processes/' + this.processId + '/export')
          .then(response => {
              window.location = response.data.url;
              ProcessMaker.alert(this.$t('The process was exported.'), 'success');
          })
          .catch(error => {
              ProcessMaker.alert(error.response.data.message, 'danger');
          });
    },
    togglePassword() {
          if (this.type == 'text') {
            this.type = 'password';
          } else {
            this.type = 'text';
          }
    },
},     
  mounted() {
  }

}
</script>

<style>
  .form-control {
    border-right: 0;
  }

  .form-btn {
    border: 1px solid #b6bfc6;
    border-left:none;
  }

  .disabled-form {
    border-right: 1px solid #b6bfc6;
  }

  .invalid {
    border-color: #E50130;
  }
</style>