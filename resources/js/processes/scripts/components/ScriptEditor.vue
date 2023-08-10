<template>
  <b-container class="h-100">
    <b-card no-body class="h-100" >
      <top-menu ref="menuScript" :options="optionsMenu" />

      <b-card-body ref="editorContainer" class="overflow-hidden p-4" >
        <b-row class="h-100">
          <b-col cols="9" class="h-100 p-0">
            <b-row class="h-100">
              <b-col cols="12" class="h-100 p-0">
                <monaco-editor
                  v-show="showEditor"
                  class="h-100"
                  v-model="code"
                  :class="{hidden: resizing}"
                  :options="monacoOptions"
                  :language="language"
                  :diff-editor="false"
                />

                <div v-if="packageAi" v-show="showDiffEditor">
                  <div class="d-flex">
                    <div class="left-header-width pb-3 pl-3">
                      <div class="card-header h-100 d-flex align-items-center justify-content-between editor-header-border">
                        <b>Current Script</b>
                      </div>
                    </div>
                    <div class="right-header-width pb-3 pl-3">
                      <div class="card-header h-100 bg-primary-light d-flex align-items-center justify-content-between editor-header-border">
                        <b>AI Generated Response</b>
                        <div>
                          <button class="btn btn-sm btn-light" @click="cancelChanges()">Cancel</button>
                          <button class="btn btn-sm btn-primary" @click="applyChanges()" v-b-tooltip.hover :title="$t('Apply recommended changes')">Apply</button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <monaco-editor
                  ref="diffEditor"
                  v-show="showDiffEditor"
                  class="diff-height"
                  :class="{hidden: resizing}"
                  :options="monacoOptionsDiff"
                  :language="language"
                  :diff-editor="true"
                  :value="newCode"
                  :original="code"
                  @hook:mounted="diffEditorMounted"
                />
              </b-col>
            </b-row>
          </b-col>
          <b-col cols="3" class="h-100 pl-5">
            <b-card no-body class="h-100">
              <b-card-header class="light-gray-background">
                <b-row class="d-flex align-items-center">
                  <b-col>{{ $t('Debugger') }}</b-col>

                  <b-col align-self="end" class="text-right">
                    <b-button
                      class="text-capitalize pl-3 pr-3"
                      :disabled="preview.executing"
                      size="sm"
                      @click="execute" >
                      <i class="fas fa-caret-square-right" />
                      {{ $t('Run') }}
                    </b-button>
                  </b-col>
                </b-row>
              </b-card-header>

              <b-card-body class="overflow-hidden p-0">
                <b-list-group class="w-100 h-100 overflow-auto">
                  <b-list-group-item class="script-toggle border-0 mb-0">
                    <b-row v-b-toggle.assistant>
                      <b-col>
                        <img :src="corneaIcon"/>
                        {{ $t('Cornea AI Assistant') }}
                      </b-col>
                      <b-col class="bg-warning rounded" cols="2">{{ $t('New') }}</b-col>
                      <b-col
                        align-self="end"
                        cols="1"
                        class="mr-2"
                      >
                        <i class="fas fa-chevron-down accordion-icon" />
                      </b-col>
                    </b-row>
                  </b-list-group-item>
                  <b-list-group-item class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="assistant">
                      <div class="m-1">
                        <b-row class="px-2">
                          <b-btn 
                            class="d-flex flex-column justify-content-center align-items-center wrap"
                            variant="light"
                          >
                            <img :src="penSparkleIcon" />
                            {{ $t('Generate Script From Text') }}
                          </b-btn>
                          <b-btn
                            class="d-flex flex-column justify-content-center align-items-center"
                            variant="light"
                          >
                            {{ $t('Document') }}
                          </b-btn>
                        </b-row>
                        <b-row class="px-2">
                          <b-btn
                            class="d-flex flex-column justify-content-center align-items-center"
                            variant="light"
                          >
                            {{ $t('Clean') }}
                          </b-btn>
                          <b-btn
                            class="d-flex flex-column justify-content-center align-items-center"
                            variant="light"
                          >
                            {{ $t('List Steps') }}
                          </b-btn>
                        </b-row>
                      </div>
                    </b-collapse>
                  </b-list-group-item>

                  <b-list-group-item class="script-toggle border-0 mb-0">
                    <b-row v-b-toggle.configuration>
                      <b-col>
                        <i class="fas fa-cog" />
                        {{ $t('Configuration') }}
                      </b-col>
                      <b-col
                        align-self="end"
                        cols="1"
                        class="mr-2"
                      >
                        <i class="fas fa-chevron-down accordion-icon" />
                      </b-col>
                    </b-row>
                  </b-list-group-item>
                  <b-list-group-item class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="configuration">
                      <monaco-editor
                        v-model="preview.config"
                        :options="{ ...monacoOptions, minimap: { enabled: false } }"
                        language="json"
                        class="editor-inspector"
                      />
                    </b-collapse>
                  </b-list-group-item>

                  <b-list-group-item class="script-toggle border-0 mb-0">
                    <b-row v-b-toggle.input>
                      <b-col>
                        <i class="fas fa-sign-in-alt" />
                        {{ $t('Sample Input') }}
                      </b-col>
                      <b-col
                        align-self="end"
                        cols="1"
                        class="mr-2"
                      >
                        <i class="fas fa-chevron-down accordion-icon" />
                      </b-col>
                    </b-row>
                  </b-list-group-item>
                  <b-list-group-item class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="input">
                      <monaco-editor
                        v-model="preview.data"
                        :options="{ ...monacoOptions, minimap: { enabled: false } }"
                        language="json"
                        class="editor-inspector"
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
                      <b-col
                        align-self="end"
                        cols="1"
                        class="mr-2"
                      >
                        <i class="fas fa-chevron-down accordion-icon" />
                      </b-col>
                    </b-row>
                  </b-list-group-item>
                  <b-list-group-item class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse
                      id="output"
                      class="bg-dark"
                      :visible="outputOpen"
                    >
                      <div class="output text-white">
                        <pre
                          v-if="preview.success"
                          class="text-white"
                        ><samp>{{ preview.output }}</samp></pre>
                        <div v-if="preview.failure">
                          <div class="text-light bg-danger">
                            {{ preview.error.exception }}
                          </div>
                          <div class="text-light text-monospace small">
                            {{ preview.error.message }}
                          </div>
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
          <i
            v-if="preview.executing"
            class="fas fa-spinner fa-spin"
          />
          <i
            v-if="preview.success"
            class="fas fa-check text-success"
          />
          <i
            v-if="preview.failure"
            class="fas fa-times-circle text-danger"
          />
        </span>
      </b-card-footer>
    </b-card>
  </b-container>
</template>

<script>
import MonacoEditor from "vue-monaco";
import _ from "lodash";
import TopMenu from "../../../components/Menu.vue";
// eslint-disable-next-line no-unused-vars
import customFilters from "../customFilters";
import autosaveMixins from "../../../modules/autosave/mixins";

export default {
  components: {
    MonacoEditor,
    TopMenu
  },
  mixins: [...autosaveMixins],
  props: {
    script: {
      type: Object,
      required: true,
    },
    scriptExecutor: {
      type: Object,
      required: true,
    },
    testData: {
      type: String,
      default: "{}",
    },
    autoSaveDelay: {
      type: Number,
      default: 5000,
    },
    isVersionsInstalled: {
      type: Boolean,
      default: false,
    },
    isDraft: {
      type: Boolean,
      default: false,
    },
    packageAi: {
      default: 0,
    },
  },
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
        },
      },
    ];

    return {
      corneaIcon: require('./../../../../img/cornea_icon.svg'),
      penSparkleIcon: require('./../../../../img/pen_sparkle_icon.svg'),
      executionKey: null,
      resizing: false,
      monacoOptions: {
        automaticLayout: true,
      },
      monacoOptionsDiff: {
        automaticLayout: true,
        originalEditable: false, // for left pane
        readOnly: true,
        enableSplitViewResizing: false,
        renderSideBySide: true,
      },
      code: this.script.code,
      changesApplied: false,
      newCode: `${this.script.code}\n $a = 3+4; \n $b = $a / 2;`,
      preview: {
        error: {
          exception: "",
          message: "",
        },
        executing: false,
        data: this.testData ? this.testData : "{}",
        config: "{}",
        output: "",
        success: false,
        failure: false,
      },
      outputOpen: true,
      optionsMenu: options,
      // eslint-disable-next-line max-len
      boilerPlateTemplate: this.$t(" \r Welcome to ProcessMaker 4 Script Editor \r To access Environment Variables use {accessEnvVar} \r To access Request Data use {dataVariable} \r To access Configuration Data use {configVariable} \r To preview your script, click the Run button using the provided input and config data \r Return an array and it will be merged with the processes data \r Example API to retrieve user email by their ID {apiExample} \r API Documentation {apiDocsUrl} \r "),
      nonce: null,
      ellipsisMenuOptions: {
        actions: [
          {
            value: "discard-draft",
            content: this.$t("Discard Draft"),
            icon: "",
          },
        ],
      },
      closeHref: "/designer/scripts",
    };
  },
  computed: {
    showEditor() {
      return !this.showDiffEditor;
    },
    showDiffEditor() {
      return this.packageAi && this.newCode !== "" && !this.changesApplied;
    },
    language() {
      return this.scriptExecutor.language;
    },
    autosaveApiCall() {
      return () => {
        this.setLoadingState(true);
        ProcessMaker.apiClient
          .put(`scripts/${this.script.id}/draft`, {
            code: this.code,
            title: this.script.title,
            description: this.script.description,
            script_executor_id: this.script.script_executor_id,
            run_as_user_id: this.script.run_as_user_id,
            timeout: this.script.timeout,
          })
          .then(() => {
            this.setVersionIndicator(true);
            window.ProcessMaker.EventBus.$emit("save-changes");
          })
          .finally(() => {
            this.setLoadingState(false);
          });
      };
    },
  },
  watch: {
    "preview.output": "handlePreviewOutputChange",
    code() {
      window.ProcessMaker.EventBus.$emit("new-changes");
      this.handleAutosave();
    },
  },
  mounted() {
    ProcessMaker.EventBus.$emit("script-builder-init", this);
    ProcessMaker.EventBus.$on("save-script", (onSuccess, onError) => {
      this.save(onSuccess, onError);
    });
    ProcessMaker.EventBus.$on("script-close", () => {
      this.onClose();
    });
    ProcessMaker.EventBus.$on("script-discard", () => {
      this.discardDraft();
    });

    window.addEventListener("resize", this.handleResize);
    const userID = document.head.querySelector("meta[name=\"user-id\"]");
    window.Echo.private(
      `ProcessMaker.Models.User.${userID.content}`,
    ).listen(".ProcessMaker\\Events\\ScriptResponseEvent", (response) => {
      this.outputResponse(response);
    });
    this.loadBoilerplateTemplate();

    // Display version indicator.
    this.setVersionIndicator();

    // Display ellipsis menu.
    this.setEllipsisMenu();
  },
  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
  },

  methods: {
    diffEditorMounted() {
    },
    applyChanges() {
      this.code = this.newCode;
      this.newCode = "";
      this.changesApplied = true;
    },
    cancelChanges() {
      this.newCode = "";
      this.changesApplied = true;
    },
    resizeEditor() {
      const domNode = this.editorReference.getDomNode();
      const { clientHeight } = this.$refs.editorContainer;
      domNode.style.height = `${clientHeight.toString()}px`;
    },
    // eslint-disable-next-line func-names
    stopResizing: _.debounce(function () {
      this.resizing = false;
    }, 50),
    handleResize() {
      this.resizing = true;
      this.stopResizing();
    },
    handlePreviewOutputChange(output) {
      if (output && !this.outputOpen) {
        this.outputOpen = true;
      }
    },
    outputResponse(response) {
      if (response.nonce !== this.nonce) {
        return;
      }

      if (this.executionKey && this.executionKey !== response.data.watcher) {
        return;
      }
      ProcessMaker.apiClient.get(`scripts/execution/${response.response.key}`).then((r) => {
        if (r.data.exception) {
          this.preview.executing = false;
          this.preview.failure = true;
          this.preview.error.exception = r.data.exception;
          this.preview.error.message = r.data.message;
        } else {
          this.preview.executing = false;
          this.preview.success = true;
          this.preview.output = r.data;
        }
      });

      if (response.status !== 200) {
        this.preview.executing = false;
        this.preview.failure = true;
        this.preview.error.exception = response.status;
        this.preview.error.message = response.response;
      }
    },
    execute() {
      this.preview.executing = true;
      this.preview.success = false;
      this.preview.failure = false;
      this.preview.output = undefined;
      // Attempt to execute a script, using our temp variables
      this.nonce = Math.random().toString(36);
      ProcessMaker.apiClient.post(`scripts/${this.script.id}/preview`, {
        code: this.code,
        data: this.preview.data,
        config: this.preview.config,
        timeout: this.script.timeout,
        nonce: this.nonce,
      }).then((response) => {
        this.executionKey = response.data.key;
      });
    },
    save(onSuccess, onError) {
      ProcessMaker.apiClient
        .put(`scripts/${this.script.id}`, {
          code: this.code,
          title: this.script.title,
          description: this.script.description,
          script_executor_id: this.script.script_executor_id,
          run_as_user_id: this.script.run_as_user_id,
          timeout: this.script.timeout,
        })
        .then((response) => {
          ProcessMaker.alert(this.$t("The script was saved."), "success");
          // Set published status.
          this.setVersionIndicator(false);
          if (typeof onSuccess === "function") {
            onSuccess(response);
          }
        }).catch((err) => {
          if (typeof onError === "function") {
            onError(err);
          }
        });
    },
    discardDraft() {
      ProcessMaker.apiClient
        .post(`/scripts/${this.script.id}/close`)
        .then(() => {
          window.location.reload();
        });
    },
    loadBoilerplateTemplate() {
      if (this.script.code === "[]") {
        switch (this.script.language) {
          case "php":
            this.code = Vue.filter("php")(this.boilerPlateTemplate);
            break;
          case "lua":
            this.code = Vue.filter("lua")(this.boilerPlateTemplate);
            break;
          case "javascript":
            this.code = Vue.filter("javascript")(this.boilerPlateTemplate);
            break;
          case "csharp":
            this.code = Vue.filter("csharp")(this.boilerPlateTemplate);
            break;
          case "java":
            this.code = Vue.filter("java")(this.boilerPlateTemplate);
            break;
          case "python":
            this.code = Vue.filter("python")(this.boilerPlateTemplate);
            break;
          default:
            break;
        }

        // Save boilerplate template to avoid issues when script code is [].
        ProcessMaker.EventBus.$emit("save-script");
      }
    },
    setVersionIndicator(isDraft = null) {
      if (this.isVersionsInstalled) {
        this.$refs.menuScript.removeItem("VersionIndicator");
        this.$refs.menuScript.addItem({
          id: "VersionIndicator",
          type: "VersionIndicator",
          section: "right",
          options: {
            is_draft: isDraft ?? this.isDraft,
          },
        }, 0);
      }
    },
    setLoadingState(isLoading = false) {
      if (this.isVersionsInstalled) {
        this.$refs.menuScript.removeItem("SavedNotification");
        this.$refs.menuScript.addItem({
          id: "SavedNotification",
          type: "SavedNotification",
          section: "right",
          options: {
            is_loading: isLoading,
          },
        }, 1);
      }
    },
    setEllipsisMenu() {
      if (this.isVersionsInstalled) {
        this.$refs.menuScript.addItem({
          id: "EllipsisMenu",
          type: "EllipsisMenu",
          section: "right",
          options: {
            actions: [...this.ellipsisMenuOptions.actions],
            data: {},
            divider: false,
          },
        }, 4);
      }
    },
  },
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
  border: 0;
  border-radius: 5px;
}
</style>
