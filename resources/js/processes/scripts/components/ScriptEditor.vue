<template>
  <b-container class="h-100">
    <b-card no-body class="h-100">
      <top-menu ref="menuScript" :options="optionsMenu"/>

      <b-card-body class="overflow-hidden p-4">
        <b-row class="h-100">
          <b-col cols="9" class="h-100">
            <monaco-editor
              :options="monacoOptions"
              v-model="code"
              :language="language"
              class="h-100"
              :class="{hidden: resizing}"
            />
          </b-col>
          <b-col cols="3" class="h-100">
            <b-card no-body class="h-100">
              <b-card-header class="light-gray-background">
                <b-row class="d-flex align-items-center">
                  <b-col>{{ $t('Debugger') }}</b-col>

                  <b-col align-self="end" class="text-right">
                    <b-button
                      class="text-capitalize pl-3 pr-3"
                      :disabled="preview.executing"
                      @click="execute"
                      size="sm"
                    >
                      <i class="fas fa-caret-square-right" />
                      {{ $t('Run') }}
                    </b-button>
                  </b-col>
                </b-row>
              </b-card-header>

              <b-card-body class="overflow-hidden p-0">
                <b-list-group class="w-100 h-100 overflow-auto">
                  <b-list-group-item class="script-toggle border-0 mb-0">
                    <b-row v-b-toggle.configuration>
                      <b-col>
                        <i class="fas fa-cog" />
                        {{ $t('Configuration') }}
                      </b-col>
                      <b-col align-self="end" cols="1" class="mr-2">
                        <i class="fas fa-chevron-down accordion-icon" />
                      </b-col>
                    </b-row>
                  </b-list-group-item>
                  <b-list-group-item class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="configuration">
                      <monaco-editor
                        :options="{ ...monacoOptions, minimap: { enabled: false } }"
                        v-model="preview.config"
                        language="json"
                        class="editor-inspector"
                        :class="{hidden: resizing}"
                      />
                    </b-collapse>
                  </b-list-group-item>

                  <b-list-group-item class="script-toggle border-0 mb-0">
                    <b-row v-b-toggle.input>
                      <b-col>
                        <i class="fas fa-sign-in-alt" />
                        {{ $t('Sample Input') }}
                      </b-col>
                      <b-col align-self="end" cols="1" class="mr-2">
                        <i class="fas fa-chevron-down accordion-icon" />
                      </b-col>
                    </b-row>
                  </b-list-group-item>
                  <b-list-group-item class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="input">
                      <monaco-editor
                        :options="{ ...monacoOptions, minimap: { enabled: false } }"
                        v-model="preview.data"
                        language="json"
                        class="editor-inspector"
                        :class="{hidden: resizing}"
                      />
                    </b-collapse>
                  </b-list-group-item>

                  <b-list-group-item class="script-toggle border-0 mb-0">
                    <b-row
                      :class="outputOpen ? null : 'collapsed'"
                      :aria-expanded="outputOpen ? 'true' : 'false'"
                      aria-controls="output"
                      @click="outputOpen = !outputOpen"
                    >
                      <b-col>
                        <i class="far fa-caret-square-right" />
                        {{ $t('Output') }}
                      </b-col>
                      <b-col align-self="end" cols="1" class="mr-2">
                        <i class="fas fa-chevron-down accordion-icon" />
                      </b-col>
                    </b-row>
                  </b-list-group-item>
                  <b-list-group-item class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="output" class="bg-dark" :visible="outputOpen">
                      <div class="output text-white">
                        <pre v-if="preview.success" class="text-white"><samp>{{ preview.output }}</samp></pre>
                        <div v-if="preview.failure">
                          <div class="text-light bg-danger">{{preview.error.exception}}</div>
                          <div class="text-light text-monospace small">{{preview.error.message}}</div>
                        </div>
                      </div>
                    </b-collapse>
                  </b-list-group-item>
                </b-list-group>
              </b-card-body>
            </b-card>
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
import TopMenu from "../../../components/Menu";

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
    TopMenu,
  },
  computed: {
    language() {
      return this.scriptExecutor.language;
    }
  },
  mounted() {
    ProcessMaker.EventBus.$emit("script-builder-init", this);
    ProcessMaker.EventBus.$on("save-script", (onSuccess, onError) => {
      this.save(onSuccess, onError);
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
    save(onSuccess, onError) {
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
          if (typeof onSuccess === "function") {
            onSuccess(response);
          }
        }).catch(err => {
          if (typeof onError === "function") {
            onError(err);
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
</style>
