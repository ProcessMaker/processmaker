<template>
  <iframe @load="loadIFrame" class="border-0 w-100 h-100"></iframe>
</template>

<script>
export default {
  props: ["config"],
  data() {
    return {};
  },
  methods: {
    loadIFrame() {
      const document = this.$el ? this.$el.contentDocument : null;
      if (document) {
        const html = this.config.html && this.config.html ? this.config.html : "";
        document.open("text/html", "replace");
        document.write(html);
        document.close();
      }
    }
  },
  watch: {
    config: {
      deep: true,
      immediate: true,
      handler() {
        this.loadIFrame();
      }
    }
  }
};
</script>
