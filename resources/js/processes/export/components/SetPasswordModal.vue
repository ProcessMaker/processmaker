<template>
  <div>
    <modal
      id="set-password-modal" 
      :title="$t('Set Password')" 
      :subtitle="$t('This password will be required when importing the exported package/process.')"
      :ok-title="$t('Export')"
      :ok-disabled="disabled" 
      @ok.prevent="verifyPassword" 
      @hidden="onClose"
    >
      <template>
        <b-form-group>
          <div v-if="ask">
            <b-form-checkbox class="pt-2" v-model="passwordProtect" switch :disabled="$root.forcePasswordProtect">
              Password Protect Export
            </b-form-checkbox>
            <small v-if="$root.forcePasswordProtect" class="text-danger">
              Password protect is required because some assets may have sensitive data.
            </small>
          </div>
            <template v-if="passwordProtect === true">
              <div class="pt-3">
                <label for="set-password">Password</label>
                <vue-password v-model="password" id="set-password" :disable-strength=true />
                <small v-if="errors.length === true" class="text-danger">{{ 'Password must have at least 8 characters.' }}</small>
              </div>
              <div class="pt-3">
                <label for="confirm-set-password">Verify Password</label>
                <vue-password v-model="confirmPassword" id="confirm-password" :disable-strength=true :class="errors.password ? 'invalid' : ''" />
                <small v-if="errors && errors.password" class="text-danger">{{ 'Must match password entered above.' }}</small>
              </div>
            </template>
            <template v-else>
              <div class="pt-3">
                <label for="set-password">Password</label>
                <vue-password :disable-strength=true :disable-toggle=true disabled />
              </div>
              <div class="pt-3">
                <label for="confirm-set-password">Verify Password</label>
                <vue-password :disable-strength=true :disable-toggle=true disabled />
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
  components: {
    Modal,
  },
  props: ["processId", "processName", "ask"],
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
        'length': null,
      },
    };
  },
  computed: {
    passwords() {
      return `${this.password}|${this.confirmPassword}`;
    },
  },
  watch: {
    passwords() {
      if (this.passwordProtect === true) {
        if (this.password.length >= 8 && this.confirmPassword) {
          this.disabled = false;
        } else if (this.password && !this.confirmPassword) {
          this.disabled = true;
        } else if (!this.password && this.confirmPassword) {
          this.disabled = true;
        }
      }
    },
    passwordProtect() {
      if (this.passwordProtect === false) {
        this.disabled = false;
        this.password = "";
        this.confirmPassword = "";
        this.errors.password = "";
      } else {
        this.disabled = this.password ? false : true;
      }
    },
    password() {
      if (this.password && this.password.length < 8) {
        this.errors.length = true;
      } else if (this.password && this.password.length >= 8) {
        this.errors.length = false;
      }
    },
  },
  methods: {
    show() {
      this.$bvModal.show('set-password-modal');
    },
    hide() {
      this.$bvModal.hide('set-password-modal');
    },
    onClose() {
      this.password = "";
      this.confirmPassword = "";
      this.errors.password = "";
    },
    verifyPassword() {
      if (this.passwordProtect && !this.validatePassword()) {
        return false;
      }
      else {
        this.$emit("verifyPassword", this.password);
        this.hide();
      }
    },
    togglePassword(reference) {
      if (this.$refs[reference].type == 'text') {
        this.$refs[reference].type = 'password';
      } else {
        this.$refs[reference].type = 'text';
      }
    },
    validatePassword() {
      if (!this.password && !this.confirmPassword) {
        return false;
      }

      if (this.password.trim() === '' && this.confirmPassword.trim() === '') {
        return false;
      }

      if (this.password.length < 8) {
        return false;
      }

      if (this.password !== this.confirmPassword) {
        this.errors.password = ['Passwords must match'];
        this.disabled = true;
        return false;
      }

      this.errors.password = null;
      return true;
    },
  },
};

</script>

<style>
  vue-password {
    width: 90%;
  }

  .invalid {
    border-color: #E50130;
  }
</style>
