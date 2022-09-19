<template>
  <div>
    <modal id="enterPassword" :title="$t('Enter Password')" :ok-disabled="disabled" @ok.prevent="onSubmit" @hidden="onClose">
      <template> 
        <b-form-group
          required
          :label="$t('Password')"
        >
          <b-form-input
            autofocus
            v-model="password"
            autocomplete="off"
            name="password"
            required
          ></b-form-input>
        </b-form-group>
      </template>
    </modal>
  </div>
</template>

<script>
  import { FormErrorsMixin, Modal, Required } from "SharedComponents";

  export default {
    components: { Modal, Required },
    mixins: [ FormErrorsMixin ],
    props: [''],
    data: function() {
      return {
        showModal: false,
        password: '',
        disabled: false
      }
    },
    methods: {
      onClose () {
        this.password = "";
      },
      onSubmit () {
        console.log('HANDLE ON SUBMIT');
      }
    }
  };
</script>
