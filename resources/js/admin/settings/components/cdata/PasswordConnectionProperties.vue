<template>
  <div>
    <b-form-group
      required
      :label="$t('User')"
      :description="
        formDescription('The user account used to authenticate.', 'user', errors)
      "
      :invalid-feedback="errorMessage('user', errors)"
      :state="errorState('user', errors)"
    >
      <b-form-input
        v-model="config.user"
        required
        autofocus
        autocomplete="off"
        :state="errorState('user', errors)"
        name="user"
        data-cy="user"
      />
    </b-form-group>

    <b-form-group
      required
      :label="$t('Password')"
      :description="
        formDescription('The password used to authenticate the user.', 'password', errors)
      "
      :invalid-feedback="errorMessage('password', errors)"
      :state="errorState('password', errors)"
    >
      <b-input-group>
        <b-form-input
          v-model="config.password"
          required
          autofocus
          autocomplete="off"
          trim
          :type="inputType"
          :state="errorState('password', errors)"
          name="password"
          data-cy="password"
        />
        <b-input-group-append>
          <b-button
            :aria-label="$t('Toggle Show Password')"
            variant="secondary"
            @click="togglePassword"
          >
            <i
              class="fas"
              :class="icon"
            />
          </b-button>
        </b-input-group-append>
      </b-input-group>
    </b-form-group>
  </div>
</template>
<script>
// eslint-disable-next-line import/no-unresolved
import { FormErrorsMixin } from "SharedComponents";

export default {
  mixins: [FormErrorsMixin],
  props: {
    formData: {
      type: Object,
      default: () => ({}),
    },
    authScheme: {
      type: String,
      default: "Password",
    },
  },
  data() {
    return {
      config: {
        AuthScheme: this.authScheme,
        user: "",
        password: "",
      },
      errors: {},
      inputType: "password",
    };
  },
  computed: {
    icon() {
      return this.inputType === "password" ? "fa-eye-slash" : "fa-eye";
    },
  },
  watch: {
    config: {
      deep: true,
      handler() {
        this.$emit("updateFormData", this.config);
      },
    },
  },
  mounted() {
    this.config = {
      ...this.config,
      ...this.formData,
    };
    this.$emit("updateFormData", this.config);
  },
  methods: {
    togglePassword() {
      this.inputType = this.inputType === "password" ? "text" : "password";
    },
  },
};
</script>
