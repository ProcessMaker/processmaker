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
        <vue-password v-model="password" id="password" :disable-strength="true" :classes="error ? ['invalid', 'form-control'] : 'form-control'" />
        <small class="form-text text-danger" v-if="passwordError">
          {{ passwordError }}
        </small>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal } from "SharedComponents";
  import VuePassword from "vue-password";

  export default {
    components: { Modal , VuePassword},
    mixins: [ FormErrorsMixin ],
    props: ['passwordError'],
    data: function() {
      return {
        showModal: false,
        password: '',
        disabled: true,
        type: 'password',
        error: null
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
        this.resetError();
      }
    },
    methods: {
      show() {
        this.$bvModal.show('enterPassword');
      },
      hide() {
        this.$bvModal.hide('enterPassword');
      },
      onClose () {
        this.password = "";
        this.resetError();
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
        if (this.password) {
          this.$emit('password', this.password);
        }
      },
      resetError() {
        this.error = null;
      },
    }
  };
</script>

<style>
  .invalid {
    border-color: #E50130;
  } 
</style>
