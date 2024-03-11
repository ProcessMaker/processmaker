<template>
  <div />
</template>
<script>
export default {
  props: {
    formData: {
      type: Object,
      default: () => ({}),
    },
  },
  data() {
    return {
      config: {
        AuthScheme: "None",
      },
    };
  },
  mounted() {
    this.config = {
      ...this.config,
      ...this.formData,
    };
  },
};
</script>
