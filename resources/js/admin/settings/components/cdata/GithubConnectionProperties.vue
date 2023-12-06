<template>
  <div>
    <b-form-group
      :label="$t('Repository Name')"
      :description="formDescription('Restrict query results by the repository name.', 'repository_name', errors)"
      :invalid-feedback="errorMessage('repository_name', errors)"
      :state="errorState('repository_name', errors)"
    >
      <b-form-input
        v-model="config.repository_name"
        autofocus
        autocomplete="off"
        :state="errorState('repository_name', errors)"
        name="repository_name"
      />
    </b-form-group>

    <b-form-group
      :label="$t('User Login')"
      :description="formDescription('Restrict query results by UserLogin.', 'user_login', errors)"
      :invalid-feedback="errorMessage('user_login', errors)"
      :state="errorState('user_login', errors)"
    >
      <b-form-input
        v-model="config.user_login"
        autofocus
        autocomplete="off"
        :state="errorState('user_login', errors)"
        name="user_login"
      />
    </b-form-group>
  </div>
</template>

<script>
import { FormErrorsMixin } from "../../../../components/shared";

export default {
  mixins: [FormErrorsMixin],
  props: ["formData"],
  data() {
    return {
      config: {
        user_login: "",
        repository_name: "",
      },
      errors: {},
    };
  },
  watch: {
    config: {
      handler() {
        this.$emit("updateFormData", this.config);
      },
      deep: true,
    },
  },
  mounted() {
    this.config.user_login = this.formData?.user_login;
    this.config.repository_name = this.formData?.repository_name;
  },
};
</script>
