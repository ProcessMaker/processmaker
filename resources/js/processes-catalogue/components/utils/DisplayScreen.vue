<template>
  <div class="screen-container">
    <vue-form-renderer
      v-model="previewData"
      :config="screen.config"
      :computed="screen.computed"
      :custom-css="screen.custom_css"
      :watchers="screen.watchers"
      :show-errors="true"
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
};
</script>

<style scoped>
.screen-container {
  padding: 16px;
}
</style>
