<template>
  <iframe @load="loadIFrame" ref="iframe" class="border-0 w-100 h-100"></iframe>
</template>

<script>
export default {
  props: ["config", "data", "token"],
  data() {
    return {
      iframeDocument: null
    };
  },
  methods: {
    loadIFrame() {
      // const document = this.$el ? this.$el.contentDocument : null;
      // if (document) {
      //   console.log("Yup got document");
      //   const html = this.config.html && this.config.html ? this.config.html : "";
      //   document.open("text/html", "replace");
      //   document.write(html);
      //   document.close();
      // }
    }
  },
  mounted() {
    this.iframeDocument = this.$refs.iframe.contentDocument;

    let content = this.config.html.replace('PM_REQUEST_DATA', JSON.stringify(this.data));
    content = content.replace('PM_API_TOKEN', this.token);
    
    this.iframeDocument.open();
    this.iframeDocument.write(content);
    this.iframeDocument.close();
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
