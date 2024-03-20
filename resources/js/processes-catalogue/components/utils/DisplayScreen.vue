<!-- eslint-disable vue/no-mutating-props -->
<template>
  <div>
    <vue-form-renderer
      v-model="previewData"
      :config="screen.config"
      :computed="screen.computed"
      :custom-css="screen.custom_css"
      :watchers="screen.watchers"
    />
  </div>
</template>

<script>
export default {
  props: ["screen"],
  data() {
    return {
      disabled: false,
      previewData: {},
    };
  },
  watch: {
    screen: {
      deep: true,
      handler() {
        this.disabled = false;
      },
    },
  },
  methods: {},
};
</script>
