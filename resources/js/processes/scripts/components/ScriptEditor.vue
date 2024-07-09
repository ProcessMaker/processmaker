<template>
  <b-container class="h-100">
    <b-card
      no-body
      class="h-100"
    >
      <top-menu
        v-show="!previewChanges"
        ref="menuScript"
        :options="optionsMenu"
      />
      <b-card-body
        ref="editorContainer"
        class="overflow-hidden p-4"
      >
        <b-row class="h-100">
          <b-col
            :cols="previewChanges && action !== 'generate' ? 12 : 9"
            class="h-100 p-0"
          >
            <b-row class="h-100 w-100">
              <b-col
                cols="12"
                class="h-100 p-0"
              >
                <div
                  v-if="packageAi"
                  v-show="showDiffEditor || showExplainEditor"
                >
                  <div class="d-flex">
                    <div class="left-header-width pb-3 pl-3">
                      <div class="card-header h-100 d-flex align-items-center justify-content-between editor-header-border">
                        <span>{{ $t('Current Script') }}</span>
                      </div>
                    </div>
                    <div
                      v-if="showDiffEditor"
                      class="right-header-width pb-3 pl-3"
                    >
                      <div class="card-header h-100 bg-primary-light d-flex align-items-center justify-content-between editor-header-border pulse header-x-padding">
                        <span>{{ $t('AI Generated Response') }}</span>
                        <div>
                          <button
                            class="btn btn-sm btn-light"
                            @click="cancelChanges()"
                          >
                            {{ $t('Cancel') }}
                          </button>
                          <button
                            v-b-tooltip.hover
                            class="btn btn-sm btn-primary"
                            :title="$t('Apply recommended changes')"
                            @click="applyChanges()"
                          >
                            {{ $t('Apply changes  ') }}
                          </button>
                        </div>
                      </div>
                    </div>
                    <div
                      v-else-if="showExplainEditor"
                      class="right-header-width pb-3 pl-3"
                    >
                      <div class="card-header h-100 bg-primary-light d-flex align-items-center justify-content-between editor-header-border header-x-padding">
                        <span>{{ $t('AI Explanation') }}</span>
                        <div>
                          <button
                            v-b-tooltip.hover
                            class="btn"
                            :title="$t('Close Explanation')"
                            @click="closeExplanation()"
                          >
                            <i class="fa fa-times" />
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div
                  class="d-flex justify-content-between"
                  :class="{'h-100': !(showExplainEditor || showDiffEditor), 'editors-container': showExplainEditor || showDiffEditor}"
                >
                  <monaco-editor
                    v-show="showEditor"
                    ref="editor"
                    v-model="code"
                    :class="[{hidden: resizing}, showExplainEditor ? 'w-50' : 'w-100']"
                    :options="monacoOptions"
                    :language="language"
                    :diff-editor="false"
                  />
                  <div
                    v-if="showExplainEditor"
                    class="w-50 h-100 py-2 px-4 explain-editor-container"
                  >
                    <div
                      class="mx-auto explain-editor pb-5"
                      v-html="newCode"
                    />
                  </div>

                  <monaco-editor
                    v-show="showDiffEditor"
                    ref="diffEditor"
                    class="diff-height w-100"
                    :class="{hidden: resizing}"
                    :options="monacoOptionsDiff"
                    :language="language"
                    :diff-editor="true"
                    :value="newCode"
                    :original="code"
                  />
                </div>
              </b-col>
            </b-row>
          </b-col>

          <!-- Progress panel -->
          <b-col
            v-if="packageAi && loading"
            cols="3"
            class="h-100"
          >
            <div class="h-100 px-5 d-flex justify-items-center justify-content-center flex-column progress-panel">
              <div class="w-100 d-flex justify-content-between text-muted">
                <div><small>{{ loaderAction }}...</small></div>
                <div v-if="progress.progress">
                  <small>{{ Math.trunc(progress.progress) }}%</small>
                </div>
              </div>
              <div class="progress">
                <div
                  class="progress-bar progress-bar-striped progress-bar-animated"
                  role="progressbar"
                  :style="{'width': progress.progress + '%'}"
                  :aria-valuenow="progress.progress"
                  aria-valuemin="0"
                  aria-valuemax="100"
                />
              </div>
              <div class="text-right progress-panel-footer">
                <button
                  class="btn btn-light"
                  @click="cancelRequest()"
                >
                  {{ $t('Cancel') }}
                </button>
              </div>
            </div>
          </b-col>

          <!-- Right panel -->
          <b-col
            v-if="!loading && !previewChanges"
            cols="3"
            class="h-100"
          >
            <b-card
              no-body
              class="h-100"
            >
              <b-card-header class="light-gray-background">
                <b-row class="d-flex align-items-center">
                  <b-col>{{ $t('Debugger') }}</b-col>

                  <b-col
                    align-self="end"
                    class="text-right"
                  >
                    <b-button
                      class="text-capitalize pl-3 pr-3"
                      :disabled="preview.executing"
                      size="sm"
                      @click="execute"
                    >
                      <i class="fas fa-caret-square-right" />
                      {{ $t('Run') }}
                    </b-button>
                  </b-col>
                </b-row>
              </b-card-header>

              <b-card-body class="overflow-hidden p-0">
                <b-list-group class="w-100 h-100 overflow-auto">
                  <ai-tab
                    v-if="packageAi"
                    ref="aiTab"
                    :user="user"
                    :source-code="code"
                    :language="language"
                    :selection="selection"
                    :package-ai="packageAi"
                    :process-id="processId"
                    :default-prompt="prompt"
                    :line-context="lineContext"
                    @get-selection="onGetSelection"
                    @request-started="onRequestStarted"
                    @current-nonce-changed="onCurrentNonceChanged"
                    @set-diff="onSetDiff"
                    @set-action="onSetAction"
                    @prompt-changed="onPromptChanged"
                  />
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
                      <b-col class="text-right">
                        <button
                          v-b-modal.data-preview
                          type="button"
                          class="btn-sm float-right"
                          @click.stop
                        >
                          <i class="fas ml-auto fas fa-expand" />
                        </button>
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
                          class="text-white text-pre-wrap"
                        ><samp>{{ preview.output }}</samp></pre>
                        <div v-if="preview.failure">
                          <div class="text-light bg-danger">
                            {{ preview.error.exception }}
                          </div>
                          <pre class="text-light text-monospace small">{{
                            preview.error.message
                          }}</pre>
                        </div>
                      </div>
                    </b-collapse>
                  </b-list-group-item>
                </b-list-group>
              </b-card-body>
            </b-card>
          </b-col>

          <!-- Right Panel generate script -->
          <b-col
            v-if="!loading && previewChanges && action ==='generate'"
            cols="3"
            class="h-100"
          >
            <b-card
              no-body
              class="h-100"
            >
              <b-card-header class="light-gray-background">
                {{ $t('Generate Script From Text') }}
              </b-card-header>

              <b-card-body class="overflow-hidden p-0">
                <b-list-group class="w-100 h-100 overflow-auto">
                  <ai-tab
                    v-if="packageAi"
                    ref="aiTab2"
                    :default-prompt="prompt"
                    :user="user"
                    :source-code="code"
                    :language="language"
                    :selection="selection"
                    :package-ai="packageAi"
                    :default-selected="'generate'"
                    :line-context="lineContext"
                    @get-selection="onGetSelection"
                    @request-started="onRequestStarted"
                    @current-nonce-changed="onCurrentNonceChanged"
                    @set-diff="onSetDiff"
                    @set-action="onSetAction"
                    @prompt-changed="onPromptChanged"
                  />
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
    <b-modal
      id="data-preview"
      hide-footer
      size="xl"
      title="Output Preview Panel"
      header-close-content="&times;"
    >
      <b-row class="h-100">
        <b-col cols="6">
          <monaco-editor
            v-model="stringifyJson"
            :options="monacoOptionsOutput"
            data-cy="editorViewFrame"
            class="editor-modal"
            language="json"
          />
        </b-col>
        <b-col cols="6">
          <tree-view
            v-model="stringifyJson"
            :iframe-height="iframeHeight"
            style="border:1px; solid gray;"
          />
        </b-col>
      </b-row>
    </b-modal>
  </b-container>
</template>

<script>
import MonacoEditor from "vue-monaco";
import _ from "lodash";
import TopMenu from "../../../components/Menu.vue";
// eslint-disable-next-line no-unused-vars
import customFilters from "../customFilters";
import autosaveMixins from "../../../modules/autosave/mixins";
import AssetRedirectMixin from "../../../components/shared/AssetRedirectMixin";
import AiTab from "./AiTab.vue";

export default {
  components: {
    MonacoEditor,
    TopMenu,
    AiTab,
  },
  mixins: [...autosaveMixins, AssetRedirectMixin],
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
      type: [String, Number],
      default: 0,
    },
    processId: {
      type: Number,
      default: 0,
    },
    user: {
      type: Object,
      required: true,
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
          ProcessMaker.EventBus.$emit("save-script", true);
        },
      },
    ];

    return {
      executionKey: null,
      iframeHeight: "600px",
      stringifyJson: "",
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
        renderOverviewRuler: false,
      },
      monacoOptionsOutput: {
        language: "json",
        lineNumbers: "off",
        readOnly: true,
        formatOnPaste: true,
        formatOnType: true,
        automaticLayout: true,
        minimap: { enabled: false },
      },
      code: this.script.code,
      newCode: "",
      prompt: "",
      action: "",
      loading: false,
      selection: null,
      lineContext: null,
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
    };
  },
  computed: {
    previewChanges() {
      return this.showDiffEditor || this.showExplainEditor;
    },
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
      return this.scriptExecutor.language === 'php-nayra' ? 'php' : this.scriptExecutor.language;
    },
    autosaveApiCall() {
      return () => {
        this.setLoadingState(true);
        ProcessMaker.apiClient
          .put(`scripts/${this.script.id}/draft`, {
            code: this.code,
            title: this.script.title,
            description: this.script.description,
            projects: this.script.projects,
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
    closeHref() {
      return this.redirectUrl ? this.redirectUrl : "/designer/scripts";
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
    ProcessMaker.EventBus.$on("save-script", (shouldRedirect, onSuccess, onError) => {
      this.save(onSuccess, onError, shouldRedirect);
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
    this.onGetSelection();

    // Display version indicator.
    this.setVersionIndicator();

    // Display ellipsis menu.
    this.setEllipsisMenu();

    if (this.processId !== 0) {
      this.prompt = `${this.script.title}\n${this.script.description}`;
    }
  },

  beforeDestroy() {
    window.removeEventListener("resize", this.handleResize);
  },

  methods: {
    applyChanges() {
      this.code = this.newCode;
      this.newCode = "";
      this.changesApplied = true;
      this.action = "";
    },
    cancelChanges() {
      this.newCode = "";
      this.changesApplied = true;
      this.action = "";
    },
    cancelRequest() {
      if (this.currentNonce) {
        this.cancelledJobs.push(this.currentNonce);
        localStorage.setItem("cancelledJobs", JSON.stringify(this.cancelledJobs));
        this.loading = false;
        this.progress.progress = 0;
        this.action = "";
      }
    },
    closeExplanation() {
      this.newCode = "";
      this.action = "";
    },
    onCurrentNonceChanged(currentNonce) {
      this.currentNonce = currentNonce;
    },
    onSetDiff(isDiff) {
      this.isDiffEditor = isDiff;
      if (this.selection) {
        this.$refs.diffEditor.getMonaco().getOriginalEditor().setSelection(this.selection);
      }
    },
    onSetAction(action) {
      this.action = action;
    },
    onPromptChanged(prompt) {
      this.prompt = prompt;
    },
    onGetSelection() {
      const editor = this.showDiffEditor ? this.$refs.diffEditor.getMonaco().getOriginalEditor() : this.$refs.editor.getMonaco();
      if (editor) {
        const context = {};
        const selection = editor.getSelection();
        this.selection = selection;

        const currentLineUnsanitized = editor.getModel().getLineContent(selection.startLineNumber);
        context.currentLine = this.sanitize(currentLineUnsanitized);
        const newStartColumnPosition = this.prevCharactersCount(currentLineUnsanitized.substring(0, selection.startColumn - 1));
        this.$set(this.selection, "newStartColumn", newStartColumnPosition);

        if (this.selection.startLineNumber === 1) {
          context.previousLine = null;
        } else {
          context.previousLine = this.sanitize(editor.getModel().getLineContent(selection.startLineNumber - 1));
        }

        if (editor.getModel().getLineCount() === selection.startLineNumber) {
          context.nextLine = null;
        } else {
          context.nextLine = this.sanitize(editor.getModel().getLineContent(selection.startLineNumber + 1));
        }

        this.lineContext = context;
      }
    },
    sanitize(string) {
      const map = {
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "\"": "&quot;",
        "'": "&#x27;",
        "/": "&#x2F;",
      };
      const reg = /[&<>"'/]/ig;
      return string.replace(reg, (match) => (map[match]));
    },
    prevCharactersCount(subString) {
      return this.sanitize(subString).length;
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

          if (response.data.nonce !== this.currentNonce) {
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
              this.getScriptVersion(response.data.scriptVersionId);
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
    getScriptVersion(scriptVersionId) {
      const url = "/package-ai/getScriptVersion";

      const params = {
        server: window.location.host,
        scriptVersionId,
      };

      ProcessMaker.apiClient
        .post(url, params)
        .then((response) => {
          this.newCode = response.data.version.diff;
        })
        .catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;

          if (error.response.status !== 404) {
            window.ProcessMaker.alert(errorMsg, "danger");
          }
        });
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
      this.stringifyJson = JSON.stringify(output, null, 2);
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
    save(onSuccess, onError, shouldRedirect = false) {
      ProcessMaker.apiClient
        .put(`scripts/${this.script.id}`, {
          code: this.code,
          title: this.script.title,
          description: this.script.description,
          projects: this.script.projects,
          script_executor_id: this.script.script_executor_id,
          run_as_user_id: this.script.run_as_user_id,
          timeout: this.script.timeout,
        })
        .then((response) => {
          window.ProcessMaker.EventBus.$emit("save-changes");
          ProcessMaker.alert(this.$t("The script was saved."), "success");
          // Set published status.
          this.setVersionIndicator(false);
          if (typeof onSuccess === "function") {
            onSuccess(response);
          }

          if (shouldRedirect) {
            if (this.processId) {
              window.location = `/modeler/${this.processId}`;
            }

            window.ProcessMaker.EventBus.$emit("redirect");
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
          case "php-nayra":
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
        const shouldRedirect = false;
        ProcessMaker.EventBus.$emit("save-script", shouldRedirect);
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

//JSON Browser
.tree-button {
      box-shadow: 2px 2px rgba($color: #000000, $alpha: 1.0);
}
.editor-modal {
  height: 600px;
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

.text-pre-wrap {
    overflow-x: hidden;
    white-space: pre-wrap;
}
</style>
