<template>
  <div
    class="screen-container"
    :class="{ 'prospect-process-shell': isProspectProcess }">
    <div class="prospect-screen-shell">
      <div class="prospect-screen-frame">
        <vue-form-renderer
          v-model="previewData"
          class="prospect-form-renderer"
          :config="screen.config"
          :computed="screen.computed"
          :custom-css="screen.custom_css"
          :watchers="screen.watchers"
          :show-errors="true"
        />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    screen: {
      type: Object,
      required: true,
    },
    process: {
      type: Object,
      default: null,
    },
  },
  data() {
    return {
      disabled: false,
      previewData: {},
    };
  },
  computed: {
    isProspectProcess() {
      return (this.process?.name || "").toLowerCase().includes("prospect");
    },
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
  padding: 16px 0;
}
</style>
