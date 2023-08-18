<template>
  <b-container class="h-100">
    <b-card no-body class="h-100" >
      <top-menu v-if="!showDiffEditor" ref="menuScript" :options="optionsMenu" />

      <b-card-body ref="editorContainer" class="overflow-hidden p-4" >
        <b-row class="h-100">
          <b-col cols="9" class="h-100 p-0">
            <b-row class="h-100">
              <b-col cols="12" class="h-100 p-0">
                <div v-if="packageAi" v-show="showDiffEditor || showExplainEditor">
                  <div class="d-flex">
                    <div class="left-header-width pb-3 pl-3">
                      <div class="card-header h-100 d-flex align-items-center justify-content-between editor-header-border">
                        <b>{{ $t('Current Script') }}</b>
                      </div>
                    </div>
                    <div v-if="showDiffEditor" class="right-header-width pb-3 pl-3">
                      <div class="card-header h-100 bg-primary-light d-flex align-items-center justify-content-between editor-header-border pulse">
                        <b>{{ $t('AI Generated Response') }}</b>
                        <div>
                          <button class="btn btn-sm btn-light" @click="cancelChanges()">{{ $t('Cancel') }}</button>
                          <button class="btn btn-sm btn-primary" @click="applyChanges()" v-b-tooltip.hover :title="$t('Apply recommended changes')">{{ $t('Apply') }}</button>
                        </div>
                      </div>
                    </div>
                    <div v-else-if="showExplainEditor" class="right-header-width pb-3 pl-3">
                      <div class="card-header h-100 bg-primary-light d-flex align-items-center justify-content-between editor-header-border">
                        <b>{{ $t('AI Explanation') }}</b>
                        <div>
                          <button class="btn" @click="closeExplanation()" v-b-tooltip.hover :title="$t('Close Explanation')">
                            <i class="fa fa-times"></i>
                        </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="d-flex justify-content-between" :class="{'h-100': !(showExplainEditor || showDiffEditor), 'editors-container': showExplainEditor || showDiffEditor}">
                  <monaco-editor
                    ref="editor"
                    v-show="showEditor"
                    class=""
                    v-model="code"
                    :class="[{hidden: resizing}, showExplainEditor ? 'w-50' : 'w-100']"
                    :options="monacoOptions"
                    :language="language"
                    :diff-editor="false"
                  />
                  <div v-if="showExplainEditor" class="w-50 h-100 py-2 px-4 explain-editor">
                    {{ newCode }}
                  </div>

                  <monaco-editor
                    ref="diffEditor"
                    v-show="showDiffEditor"
                    class="diff-height w-100"
                    :class="{hidden: resizing}"
                    :options="monacoOptionsDiff"
                    :language="language"
                    :diff-editor="true"
                    :value="newCode"
                    :original="code"
                    @hook:mounted="diffEditorMounted"
                  />
                </div>
              </b-col>
            </b-row>
          </b-col>

          <!-- Progress panel -->
          <b-col v-if="packageAi && loading" cols="3" class="h-100 pl-5">
            <div class="h-100 px-5 d-flex justify-items-center justify-content-center flex-column progress-panel">
              <div class="w-100 d-flex justify-content-between text-muted">
                <div><small>{{ loaderAction }}...</small></div>
                <div v-if="progress.progress"><small>{{ Math.trunc(progress.progress) }}%</small></div>
              </div>
              <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" :style="{'width': progress.progress + '%'}" :aria-valuenow="progress.progress" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <div class="text-right progress-panel-footer">
                <button class="btn btn-light" @click="cancelRequest()">{{ $t('Cancel') }}</button>
              </div>
            </div>
          </b-col>

          <!-- Right panel -->
          <b-col v-if="!loading" cols="3" class="h-100 pl-5">
            <b-card no-body class="h-100">
              <b-card-header v-if="!showDiffEditor" class="light-gray-background">
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
                  <cornea-tab
                    @get-selection="onGetSelection"
                    @request-started="onRequestStarted"
                    @current-nonce-changed="onCurrentNonceChanged"
                    @set-diff="onSetDiff"
                    :user="user"
                    :sourceCode="code"
                    :language="language"
                    :selection="selection"
                    :package-ai="packageAi"
                    :showPreview="showDiffEditor"
                  />
                  <b-list-group-item v-if="!showDiffEditor" class="script-toggle border-0 mb-0">
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
                  <b-list-group-item v-if="!showDiffEditor" class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="configuration">
                      <monaco-editor
                        v-model="preview.config"
                        :options="{ ...monacoOptions, minimap: { enabled: false } }"
                        language="json"
                        class="editor-inspector"
                      />
                    </b-collapse>
                  </b-list-group-item>

                  <b-list-group-item v-if="!showDiffEditor" class="script-toggle border-0 mb-0">
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
                  <b-list-group-item v-if="!showDiffEditor" class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
                    <b-collapse id="input">
                      <monaco-editor
                        v-model="preview.data"
                        :options="{ ...monacoOptions, minimap: { enabled: false } }"
                        language="json"
                        class="editor-inspector"
                      />
                    </b-collapse>
                  </b-list-group-item>

                  <b-list-group-item  v-if="!showDiffEditor" class="script-toggle border-0 mb-0">
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
                  <b-list-group-item v-if="!showDiffEditor" class="p-0 border-left-0 border-right-0 border-top-0 mb-0">
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
import CorneaTab from "./CorneaTab.vue";

export default {
  components: {
    MonacoEditor,
    TopMenu,
    CorneaTab,
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
    user: {
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
        loaderAction: "",
        action: () => {
          ProcessMaker.EventBus.$emit("save-script");
        },
      },
    ];

    return {
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
      newCode: "",
      loading: false,
      selection: null,
      isDiffEditor: false,
      currentNonce: null,
      progress: {
        progress: 0,
      },
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
      changesApplied: true,
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
      return this.packageAi && this.newCode !== "" && !this.changesApplied && this.isDiffEditor;
    },
    showExplainEditor() {
      return this.packageAi && this.newCode !== "" && !this.changesApplied && !this.isDiffEditor;
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
    if (!localStorage.getItem("cancelledJobs") || localStorage.getItem("cancelledJobs") === "null") {
      this.cancelledJobs = [];
    } else {
      this.cancelledJobs = JSON.parse(localStorage.getItem("cancelledJobs"));
    }

    this.subscribeToProgress();

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
    applyChanges() {
      this.code = this.newCode;
      this.newCode = "";
      this.changesApplied = true;
    },
    cancelChanges() {
      this.newCode = "";
      this.changesApplied = true;
    },
    cancelRequest() {
      if (this.currentNonce) {
        this.cancelledJobs.push(this.currentNonce);
        localStorage.setItem("cancelledJobs", JSON.stringify(this.cancelledJobs));
        this.loading = false;
        this.progress.progress = 0;
      }
    },
    closeExplanation() {
      this.newCode = "";
    },
    onCurrentNonceChanged(currentNonce) {
      this.currentNonce = currentNonce;
    },
    onSetDiff(isDiff) {
      this.isDiffEditor = isDiff;
    },
    onGetSelection() {
      const editor = this.$refs.editor.getMonaco();
      if (editor) {
        const selection = editor.getSelection();
        this.selection = selection;
      }
    },
    onRequestStarted(progress, action) {
      this.loading = true;
      this.progress = progress;
      this.loaderAction = action;
    },
    subscribeToProgress() {
      const channel = `ProcessMaker.Models.User.${this.user.id}`;
      const streamProgressEvent = ".ProcessMaker\\Package\\PackageAi\\Events\\GenerateScriptProgressEvent";
      window.Echo.private(channel).listen(
        streamProgressEvent,
        (response) => {
          if (response.data.promptSessionId !== localStorage.promptSessionId) {
            return;
          }

          if (this.cancelledJobs.some((element) => element === response.data.nonce)) {
            return;
          }

          if (response.data) {
            if (response.data.progress.status === "running") {
              this.loading = true;
              this.progress = response.data.progress;
            } else if (response.data.progress.status === "error") {
              this.loading = false;
              this.progress.progress = 0;
              window.ProcessMaker.alert(response.data.message, "danger");
            } else {
              this.newCode = response.data.diff;
              this.progress.progress = 100;
              setTimeout(() => {
                this.loading = false;
                this.progress.progress = 0;
                this.changesApplied = false;
              }, 500);
            }
          }
        },
      );
    },
    diffEditorMounted() {
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
  height: calc(100% - 4.1rem);
}
.explain-editor {
  white-space: pre-line;
  overflow-y: auto;
}

.pulse {
  animation: pulse-animation 2s infinite;
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
