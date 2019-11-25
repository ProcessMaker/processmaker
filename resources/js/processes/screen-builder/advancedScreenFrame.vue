<template>
  <iframe ref="iframe" class="border-0 w-100 h-100"></iframe>
</template>

<script>
export default {
  props: ["config", "data", "token", "submiturl"],
  data() {
    return {
      iframeDocument: null
    };
  },
  methods: {
    loadIFrame() {
      if (this.iframeDocument) {
        const content = this.getContentWithDefaultVariables();
        this.iframeDocument.open();
        this.iframeDocument.write(content);
        this.iframeDocument.close();
      }
    },
    getContentWithDefaultVariables() {
      const variables = {
        PM_API_TOKEN: this.token,
        PM_SUBMIT_URL: this.submiturl,
        PM_REQUEST_DATA: this.data,
        PM_FN_COMPLETE_TASK: function(form) {
          let fields = {};
          for (var pair of new FormData(form).entries()) {
            fields[pair[0]] = pair[1];
          }
          console.log(fields);
          return false;
        }
      };
      let declareVariables = [];
      Object.keys(variables).forEach(key => {
        declareVariables.push(
          key +
            "=" +
            (variables[key] instanceof Function
              ? variables[key].toString()
              : JSON.stringify(variables[key]))
        );
      });
      return this.config.html.replace(
        "/** LOAD_PM_VARIABLES **/",
        "let " + declareVariables.join(",") + ";"
      );
    },
  },
  mounted() {
    this.iframeDocument = this.$refs.iframe.contentDocument;
    this.loadIFrame();
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
