<template>
  <iframe @load="loadIFrame" ref="iframe" class="border-0 w-100 h-100"></iframe>
</template>

<script>
export default {
  props: ["config", "data", "token", 'submiturl'],
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

    const variables = {
      PM_API_TOKEN: this.token,
      PM_SUBMIT_URL: this.submiturl,
      PM_REQUEST_DATA: this.data,
      PM_FN_COMPLETE_TASK: function(form) {
        let fields = {};
        for(var pair of new FormData(form).entries()) {
          fields[pair[0]] = pair[1];
        }
        console.log(fields);
        return false;
      }
    };
    let declareVariables = [];
    Object.keys(variables).forEach((key) => {
      declareVariables.push(key + "=" + (variables[key] instanceof Function ? variables[key].toString(): JSON.stringify(variables[key])));
    });
    let content = this.config.html.replace('/** LOAD_PM_VARIABLES **/', 'let ' + declareVariables.join(',') + ';');
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
