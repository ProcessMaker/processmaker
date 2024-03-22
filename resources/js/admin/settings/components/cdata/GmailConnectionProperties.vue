<template>
  <div />
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
  },
  data() {
    return {
      errors: {},
      config: {
        Schema: "REST",
        Scope: "https://mail.google.com/",
      },
    };
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
    this.$emit("updateFormData", this.config);
  },
};
</script>
