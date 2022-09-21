<template>
  <div>
    <modal 
      id="enterPassword" 
      :title="$t('Enter Password')" 
      :subtitle="$t('This file is password protected. Enter the password below to continue with the import.')" 
      :ok-title="$t('Import')"
      :ok-disabled="disabled" 
      @ok.prevent="verifyPassword" 
      @hidden="onClose"
    >
      <template> 
        <b-form-group
          :label="$t('Password')"
          :state="errorState('password', errors)"
          :invalid-feedback="errorMessage('password', errors)"
        >
         <b-input-group>
            <b-form-input
              autofocus
              ref="input"
              v-model="password"
              :type="type"
              autocomplete="off"
              name="password"
              class="form-control"
              :state="errorState('password', errors)"
              required
            ></b-form-input>
            <b-input-group-append>
              <b-button :aria-label="$t('Toggle Show Password')" variant="link" @click="togglePassword" class="form-btn" :class="errors.password ? 'invalid' : ''">
                <i class="fas text-secondary" :class="icon"></i>
              </b-button>
            </b-input-group-append>
         </b-input-group>
        </b-form-group>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal } from "SharedComponents";

  export default {
    components: { Modal },
    mixins: [ FormErrorsMixin ],
    props: [''],
    data: function() {
      return {
        showModal: false,
        password: '',
        disabled: true,
        type: 'password',
        errors: {
          'password': null,
        }
      }
    },
    computed: {
      icon() {
        if (this.type == 'password') {
          return 'fa-eye';
        } else {
          return 'fa-eye-slash';
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
      show() {
        this.$bvModal.show('enterPassword');
      },
      onClose () {
        this.password = "";
      },
      togglePassword() {
        if (this.type == 'text') {
          this.type = 'password';
        } else {
          this.type = 'text';
        }
        this.$refs.input.focus();
      },
      verifyPassword() {
         // TODO: IMPORT/EXPORT Verify process password
        if (this.password === 'test') {
          this.$bvModal.hide('enterPassword');
          this.$emit('verified-password');
        } else {
          this.errors.password = ['Invalid Password'];
        }
      },
      resetErrors() {
        this.errors = Object.assign({}, {
          password: null,
        });
      },
    }
  };
</script>

<style scoped>
  .form-control {
    border-right: 0;
  }

  .form-btn {
    border: 1px solid #b6bfc6;
    border-left:none;
  }

  .invalid {
    border-color: #E50130;
  }
</style>
