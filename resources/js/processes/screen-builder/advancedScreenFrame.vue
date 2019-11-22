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
      const html = this.config.html && this.config.html ? this.config.html : "";
      const document = this.$el.contentDocument;
      if (document) {
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
