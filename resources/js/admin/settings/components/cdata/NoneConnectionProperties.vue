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
      config: {},
    };
  },
  mounted() {
    this.config = this.formData;
  },
};
</script>
