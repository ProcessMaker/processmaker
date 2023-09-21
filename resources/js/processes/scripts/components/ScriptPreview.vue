<template>
  <b-container class="h-100">
    <b-card no-body class="h-100">
      <b-card-body class="overflow-hidden p-4">
        <b-row class="h-100">
          <b-col cols="12" class="h-100">
            <monaco-editor
                :options="monacoOptions"
                v-model="code"
                :language="language"
                class="h-100"
                :class="{hidden: resizing}"
            />
          </b-col>
        </b-row>
      </b-card-body>

      <b-card-footer class="d-flex">
        <span class="text-secondary text-sm">
          Language:
          <span class="text-uppercase">{{ language }}</span>
        </span>
        <span class="ml-auto">
          <i v-if="preview.executing" class="fas fa-spinner fa-spin"></i>
          <i v-if="preview.success" class="fas fa-check text-success"></i>
          <i v-if="preview.failure" class="fas fa-times-circle text-danger"></i>
        </span>
      </b-card-footer>
    </b-card>
  </b-container>
</template>

<script>
import MonacoEditor from "vue-monaco";
import _ from "lodash";
import customFilters from "../customFilters";
import MenuScript from "../../../components/Menu";

export default {
  props: ["process", "script", "scriptExecutor", "testData"],
  data() {
    const options = [
      {
        id: "button_script_save",
        section: "right",
        type: "button",
        title: this.$t("Save Script"),
        name: this.$t("Save"),
        icon: "fas fa-save",
        action: () => {
          ProcessMaker.EventBus.$emit("save-script");
        }
      }
    ];

    return {
      executionKey: null,
      resizing: false,
      monacoOptions: {
        automaticLayout: true
      },
      code: this.script.code,
      preview: {
        error: {
          exception: '',
          message: ''
        },
        executing: false,
        data: this.testData ? this.testData : "{}",
        config: "{}",
        output: "",
        success: false,
        failure: false
      },
      outputOpen: true,
      optionsMenu: options,
      boilerPlateTemplate: this.$t(` \r Welcome to ProcessMaker 4 Script Editor \r To access Environment Variables use {accessEnvVar} \r To access Request Data use {dataVariable} \r To access Configuration Data use {configVariable} \r To preview your script, click the Run button using the provided input and config data \r Return an array and it will be merged with the processes data \r Example API to retrieve user email by their ID {apiExample} \r API Documentation {apiDocsUrl} \r `),
    };
  },
  watch: {
    "preview.output"(output) {
      if (output && !this.outputOpen) {
        this.outputOpen = true;
      }
    }
  },
  components: {
    MonacoEditor,
    MenuScript,
  },
  computed: {
    language() {
      return this.scriptExecutor.language;
    }
  },
  mounted() {
    ProcessMaker.EventBus.$emit("script-builder-init", this);
    ProcessMaker.EventBus.$on("save-script", (resolve, reject) => {
      this.save(resolve, reject);
    });

    window.addEventListener("resize", this.handleResize);
    let userID = document.head.querySelector('meta[name="user-id"]');
    window.Echo.private(
        `ProcessMaker.Models.User.${userID.content}`
    ).notification(response => {
      this.outputResponse(response);
    });
    this.loadBoilerplateTemplate();
  },
  beforeDestroy: function() {
    window.removeEventListener("resize", this.handleResize);
  },

  methods: {
    outputResponse(response) {
      if (this.executionKey && this.executionKey !== response.data.watcher) {
        return;
      }
      ProcessMaker.apiClient.get("scripts/execution/" + response.response.key).then((response) => {
        if (response.data.exception) {
          this.preview.executing = false;
          this.preview.failure = true;
          this.preview.error.exception = response.data.exception;
          this.preview.error.message = response.data.message;
        } else {
          this.preview.executing = false;
          this.preview.success = true;
          this.preview.output = response.data;
        }
      });

      if (response.status !== 200) {
        this.preview.executing = false;
        this.preview.failure = true;
        this.preview.error.exception = response.status;
        this.preview.error.message = response.response;
      }
    },
    stopResizing: _.debounce(function() {
      this.resizing = false;
    }, 50),
    handleResize() {
      this.resizing = true;
      this.stopResizing();
    },
    execute() {
      this.preview.executing = true;
      this.preview.success = false;
      this.preview.failure = false;
      this.preview.output = undefined;
      // Attempt to execute a script, using our temp variables
      ProcessMaker.apiClient.post("scripts/" + this.script.id + "/preview", {
        code: this.code,
        data: this.preview.data,
        config: this.preview.config,
        timeout: this.script.timeout
      }).then((response) => {
        this.executionKey = response.data.key;
      });
    },
    onClose() {
      window.location.href = "/designer/scripts";
    },
    save(resolve, reject) {
      ProcessMaker.apiClient
          .put("scripts/" + this.script.id, {
            code: this.code,
            title: this.script.title,
            description: this.script.description,
            script_executor_id: this.script.script_executor_id,
            run_as_user_id: this.script.run_as_user_id,
            timeout: this.script.timeout,
            description: this.script.description
          })
          .then(response => {
            ProcessMaker.alert(this.$t("The script was saved."), "success");
            if (typeof resolve === "function") {
              resolve(response);
            }
          }).catch(err => {
        if (typeof reject === "function") {
          reject(err);
        }
      });
    },
    loadBoilerplateTemplate() {
      if (this.script.code === `[]`) {
        switch(this.script.language) {
          case 'php':
            this.code = Vue.filter('php')(this.boilerPlateTemplate);
            break;
          case 'lua':
            this.code = Vue.filter('lua')(this.boilerPlateTemplate);
            break;
          case 'javascript':
            this.code = Vue.filter('javascript')(this.boilerPlateTemplate);
            break;
          case 'csharp':
            this.code = Vue.filter('csharp')(this.boilerPlateTemplate);
            break;
          case 'java':
            this.code = Vue.filter('java')(this.boilerPlateTemplate);
            break;
          case 'python':
            this.code = Vue.filter('python')(this.boilerPlateTemplate);
            break;
        }

      }
    }
  }
};
</script>


<style lang="scss" scoped>
.custom-alert {
  z-index: 999;
  position: absolute;
  left: 0;
  right: 0;
  width: 800px;
  margin: auto;
  top: 3px;
}
.container {
  max-width: 100%;
  padding: 0 0 0 0;
}

.script-toggle {
  cursor: pointer;
  user-select: none;
  background: #f7f7f7;
}

.accordion-icon {
  transition: all 200ms;
}

.collapsed .accordion-icon {
  transform: rotate(-90deg);
}

.editor-inspector {
  height: 200px;
}

.output {
  min-height: 300px;
}

.diff-height {
  height: calc(100% - 30px);
}

.bg-primary-light {
  background: #CBDFFF;
}

.left-header-width {
  width: calc(50% - 9px);
}

.right-header-width {
  width: calc(50% + 9px);
}

.editor-header-border {
  border: 1px solid;
  border-radius: 0.125rem;
  padding: 0.52rem;
  border-color: #e3e3e3;
}

.editor-header-border.bg-primary-light {
  border-color: #8AB8FF;
}
.progress {
  height: 0.7rem;
  border-radius: 1em;
}

.progress-panel {
  background: #f8f8f8;
  border: 1px solid #dee2e6;
  border-radius: 2px;
}
.progress-panel-footer {
  position: absolute;
  bottom: 1rem;
  right: 2rem;
}
.editors-container {
  height: calc(100% - 1rem);
  // text-align: justify;
}
.explain-editor-container {
  white-space: pre-line;
  overflow-y: auto;
}
.explain-editor {
  max-width: 700px;
}
.pulse {
  animation: pulse-animation 2s infinite;
}
.header-x-padding {
  padding-left: .8rem;
  padding-right: .8rem;
}
@keyframes pulse-animation {
  0% {
    box-shadow: 0 0 0 0px rgb(28 114 194 / 50%);;
  }
  100% {
    box-shadow: 0 0 0 13px rgba(0, 0, 0, 0);
  }
}
</style>

<style lang="scss">
.summary-header {
  font-size: 130%;
}
.summary-content {
  border: 0;
  border-left: 5px solid #1c72c2;
  background: #cbdfff47;
  color: #114a75;
  text-align: justify;
  border-radius: 3px;
  padding: 1.5rem;
}
.explanation-header {
  font-size: 130%;
}

.blink {
  animation: blink-animation .8s infinite;
}
@keyframes blink-animation {
  0% { opacity: 0 }
  100% { opacity: 1 }
}

// Monaco editor diff styles
.monaco-diff-editor .line-insert, .monaco-diff-editor .char-insert, .monaco-editor .margin-view-overlays .cldr {
  background: #e6ffec !important;
}
.monaco-editor .gutter-insert, .monaco-diff-editor .gutter-insert {
  background-color: #c7f8d3 !important;
}
.cldr.insert-sign.codicon.codicon-diff-insert {
  background: #c7f8d3 !important;
}
.monaco-diff-editor .line-delete, .monaco-diff-editor .char-delete, .monaco-editor .margin-view-overlays .cldr {
  background: #ffebe9 !important;
}
.monaco-editor .gutter-delete, .monaco-diff-editor .gutter-delete {
  background-color: rgba(255, 0, 0, 0.2) !important;
}
.cldr.delete-sign.codicon.codicon-diff-remove {
  background: #fbc7c7 !important;
}
</style>
