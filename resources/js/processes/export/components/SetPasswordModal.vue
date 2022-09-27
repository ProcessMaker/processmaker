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
                <b-input-group>
                  <vue-password v-model="password" :disable-toggle=true :disable-strength=true>
                    <div slot="password-input" slot-scope="props">
                  <b-form-input 
                    autofocus
                    ref="input"
                    id="set-password"
                    :type="type"
                    v-model="password"
                    autocomplete="off"
                    name="password"
                    class="form-control"
                    :value="props.value"
                    @input="props.updatePassword"
                  ></b-form-input>
                  </div>
                  </vue-password>
                  <b-input-group-append>
                    <b-button :aria-label="$t('Toggle Show Password')" variant="link" @click="togglePassword" class="form-btn" :class="errors.password ? 'invalid' : ''">
                      <i class="fas text-secondary" :class="icon"></i>
                    </b-button>
                  </b-input-group-append>
                </b-input-group>
              </div>
              <div class="pt-3">
                <label for="confirm-set-password">Verify Password</label>
                <b-input-group>
                  <vue-password v-model="confirmPassword" :disable-toggle=true :disable-strength=true>
                  <div slot="password-input" slot-scope="props">
                  <b-form-input
                    id="confirm-set-password" 
                    :type="type"
                    v-model="confirmPassword"
                    autocomplete="off"
                    name="confirm-password"
                    class="form-control"
                    :value="props.value"
                  ></b-form-input>
                  </div>
                  </vue-password>
                  <b-input-group-append>
                    <b-button :aria-label="$t('Toggle Show Password')" variant="link" @click="togglePassword" class="form-btn" :class="errors.password ? 'invalid' : ''">
                      <i class="fas text-secondary" :class="icon"></i>
                    </b-button>
                  </b-input-group-append>
                  <small v-if="errors && errors.password && errors.password.length" class="text-danger">{{ 'Must match password entered above.' }}</small>
                </b-input-group>
              </div>
            </template>
            <template v-else>
              <div class="pt-3">
                <label for="set-password">Password</label>
                <b-input-group>
                  <b-form-input
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
    <export-success-modal :processName="processName"></export-success-modal>
  </div>
</template>

<script>
import { FormErrorsMixin, Modal } from "SharedComponents";
import ExportSuccessModal from './ExportSuccessModal.vue';

export default {
  components: { 
    Modal,
    ExportSuccessModal
    },
  props: ["processId", "processName"],
  mixins: [ FormErrorsMixin ],
  data() {
      return {
        passwordProtect: true,
        disabled: true,
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
    },
    passwordProtect() {
      this.disabled = this.passwordProtect ? true : false;
    }
  },
  methods: { 
    onClose() {
      this.password = "";
      this.confirmPassword = "";
    },
    onExport() {
      if (this.passwordProtect && !this.validatePassword()) {
          return false;
      }
      else {
      ProcessMaker.apiClient.post('processes/' + this.processId + '/export')
      .then(response => {
          window.location = response.data.url;
          this.$bvModal.hide('setPasswordModal');
      })
      .catch(error => {
          ProcessMaker.alert(error.response.data.message, 'danger');
      });

      this.$bvModal.show('exportSuccessModal');
      }
    },
    togglePassword() {
      if (this.type == 'text') {
        this.type = 'password';
      } else {
        this.type = 'text';
      }
    },
    validatePassword() {
      if (!this.password && !this.confirmPassword) {
          return false
      }

      if (this.password.trim() === '' && this.confirmPassword.trim() === '') {
          return false
      }

      if (this.password !== this.confirmPassword) {
          this.errors.password = ['Passwords must match']
          return false
      }

      this.errors.password = null
      return true
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