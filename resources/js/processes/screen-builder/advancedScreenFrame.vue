<template>
  <iframe ref="iframe" class="border-0 w-100 h-100"></iframe>
</template>

<script>
export default {
  props: ["config", "data", "token", "submiturl", "tokenId", "listenProcessEvents"],
  data() {
    return {
      iframeDocument: null
    };
  },
  methods: {
    submit(data) {
      let message = this.$t("Task Completed Successfully");
      ProcessMaker.apiClient
        .put("tasks/" + this.tokenId, {
          status: "COMPLETED",
          data: data
        })
        .then(() => {
          window.ProcessMaker.alert(message, "success", 5, true);
          if (!this.listenProcessEvents) {
            document.location.href = "/tasks";
          } else {
            document.location.reload();
          }
        })
        .catch(error => {
          let message =
            (error.response.data &&
              error.response.data.errors &&
              this.displayErrors(error.response.data.errors)) ||
            (error && error.message);
          ProcessMaker.alert(error.response.data.message, "danger");
          ProcessMaker.alert(message, "danger");
        });
    },
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
          parent.submitForm(fields);
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
    }
  },
  mounted() {
    this.iframeDocument = this.$refs.iframe.contentDocument;
    this.loadIFrame();
    window.submitForm = data => {
      this.submit(data);
    };
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
