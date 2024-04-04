<template>
  <div v-if="defaultSelected === 'generate'" class="h-100">
    <generate-script-text-prompt
      v-if="showPromptArea || defaultSelected === 'generate'"
      :default-prompt="defaultPrompt"
      :prompt-session-id="promptSessionId"
      @generate-script="onGenerateScript"
    />
  </div>
  <div v-else>
    <b-list-group-item class="script-toggle border-0 mb-0">
      <b-row v-b-toggle.assistant data-test="cornea-tab-toggle">
        <b-col v-if="!showPromptArea">
          <img class="mb-1 ai-icon" :src="proceC2Icon" width="18" :alt="$t('AI Assistant Icon')"/>
          {{ $t("AI Assistant") }}
        </b-col>
        <b-col
          v-else
          data-test="arrow-generate-back"
          @click="showMenu()"
        >
          <i class="mr-2 fa fa-arrow-left" />
          {{ $t("Generate Script From Text") }}
        </b-col>
        <b-col v-if="!showPromptArea" class="p-0 text-right" cols="3">
          <span class="text-center px-2 bg-warning rounded small">
            {{ $t("NEW") }}
          </span>
        </b-col>
        <b-col align-self="end" cols="1" class="mr-2">
          <i class="fas fa-chevron-down accordion-icon" />
        </b-col>
      </b-row>
    </b-list-group-item>

    <b-list-group-item
      class="p-0 border-left-0 border-right-0 border-top-0 mb-0"
    >
      <b-collapse id="assistant" :visible="true">
        <div v-if="!showPromptArea">
          <div class="card-header m-0 d-flex border-0 pb-1 px-2">
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
                data-test="generate-script-btn"
                @click="showPromptArea = true"
                v-b-tooltip.hover.bottom
                :title="$t('An AI generated scripts will be inserted as part of your code.')"
              >
                <div>
                  <img :src="penSparkleIcon" />
                </div>
                <div class="text-center">
                  {{ $t("Generate Script From Text") }}
                </div>
              </div>
            </div>
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
                data-test="document-script-btn"
                @click="documentScript()"
                v-b-tooltip.hover.bottom
                :title="$t('AI will document all your code.')"
              >
                <div>
                  <img :src="bookIcon" />
                </div>
                <div class="text-center">
                  {{ $t("Document") }}
                </div>
              </div>
            </div>
          </div>

          <div class="card-header m-0 d-flex border-0 pt-0 px-2">
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
                data-test="clean-script-btn"
                @click="cleanScript()"
                v-b-tooltip.hover.bottom
                :title="$t('AI will clean and define the portion of code you have selected.')"
              >
                <div>
                  <img :src="brushIcon" />
                </div>
                <div class="text-center">
                  {{ $t("Clean") }}
                </div>
              </div>
            </div>
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
                data-test="list-steps-btn"
                @click="explainScript()"
                v-b-tooltip.hover.bottom
                :title="$t('AI will generate an explanation of the portion of code you have selected.')"
              >
                <div>
                  <img :src="listIcon" />
                </div>
                <div class="text-center">
                  {{ $t("Explain") }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <generate-script-text-prompt
          v-if="showPromptArea || defaultSelected === 'generate'"
          :prompt-session-id="promptSessionId"
          :autofocus="true"
          :default-prompt="defaultPrompt"
          @generate-script="onGenerateScript"
        />

        <div v-if="error" class="pb-3 px-3 bg-assistant-buttons">
          <div class="alert alert-error m-0 text-center px-2  ">{{ error }}</div>
        </div>
      </b-collapse>
    </b-list-group-item>
  </div>
</template>
<script>
import GenerateScriptTextPrompt from "./GenerateScriptTextPrompt.vue";

export default {
  name: "AiTab",
  components: {
    GenerateScriptTextPrompt,
  },
  props: [
    "user",
    "sourceCode",
    "language",
    "selection",
    "packageAi",
    "defaultSelected",
    "defaultPrompt",
    "lineContext",
    "processId",
    "scriptTitle",
    "scriptDescription",
  ],
  data() {
    return {
      showPromptArea: false,
      proceC2Icon: require("./../../../../img/proceC2Black.svg"),
      penSparkleIcon: require("./../../../../img/pen_sparkle_icon.svg"),
      bookIcon: require("./../../../../img/book_icon.svg"),
      brushIcon: require("./../../../../img/brush_icon.svg"),
      listIcon: require("./../../../../img/list_icon.svg"),
      changesApplied: false,
      newCode: `\n $a = 3+4; \n $b = $a / 2;`,
      loading: false,
      promptSessionId: "",
      prompt: "",
      error: "",
      progress: {
        progress: 0,
      },
    };
  },

  mounted() {
    if (this.packageAi) {
      this.promptSessionId = localStorage.promptSessionId;
      this.currentNonce = localStorage.currentNonce;

      this.getPromptSession();
    }

    if (this.defaultPrompt) {
      this.prompt = this.defaultPrompt;
    }

    if (this.processId === 0) {
      this.showMenu();
    }
  },
  methods: {
    showMenu() {
      this.showPromptArea = false
    },
    getSelection() {
      this.$emit("get-selection");
    },
    getNonce() {
      const max = 999999999999999;
      const nonce = Math.floor(Math.random() * max);
      this.currentNonce = nonce;
      localStorage.currentNonce = this.currentNonce;
      this.$emit("current-nonce-changed", this.currentNonce);
    },
    getPromptSession() {
      const url = "/package-ai/getPromptSessionHistory";

      let params = {
        server: window.location.host,
        tenant: this.user.id,
        userId: this.user.id,
        userName: this.user.username,
      };

      if (
        this.promptSessionId &&
        this.promptSessionId !== null &&
        this.promptSessionId !== ""
      ) {
        params = {
          promptSessionId: this.promptSessionId,
        };
      }

      ProcessMaker.apiClient
        .post(url, params)
        .then((response) => {
          this.promptSessionId = response.data.promptSessionId;
          localStorage.promptSessionId = response.data.promptSessionId;
        })
        .catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;

          if (error.response.status === 404) {
            localStorage.promptSessionId = "";
            this.promptSessionId = "";
            this.getPromptSession();
          } else {
            console.error(errorMsg);
          }
        });
    },
    onGenerateScript(prompt) {
      this.prompt = prompt;
      this.$emit("prompt-changed", prompt);
      this.generateScript();
    },
    async generateScript() {
      this.getNonce();
      this.$emit("set-diff", true);
      this.$emit("set-action", "generate");
      this.getSelection();

      await this.$nextTick();

      const startColumn = this.selection.startColumn;
      const endColumn = this.selection.endColumn;
      const startLineNumber = this.selection.startLineNumber;
      const endLineNumber = this.selection.endLineNumber;
      const newStartColumn = this.selection.newStartColumn;

      if (startLineNumber === endLineNumber && startColumn === endColumn) {
        ProcessMaker.confirmModal(
          this.$t("Before you continue"),
          `<div class="mb-4 font-weight-bold">${this.$t("Ensure the cursor is positioned where you intend to place the generated script.")}</div>
          <div class="mb-2">${this.$t("Current cursor position:")}</div>
          <pre class="d-flex mb-0 text-muted flex-column code-preview">
            ${this.lineContext.previousLine === null ? "" : '<div class="w-100 text-center pb-3">...</div>'}
            <div class="d-flex">
              <div class="line-number-preview">${(startLineNumber - 1) > 0 ? startLineNumber - 1 : ''}</div>
              <div>${this.lineContext.previousLine !== null ? this.lineContext.previousLine : ''}</div>
            </div>
            <div class="d-flex align-items-center">
              <div class="line-number-preview">${startLineNumber}</div>
              <div>${this.lineContext.currentLine.substring(0, newStartColumn)}</div>
              <span class="blink text-primary cursor-preview">|</span>
              <div>${this.lineContext.currentLine.substring(newStartColumn)}</div>
            </div>
            <div class="d-flex">
              <div class="line-number-preview">${this.lineContext.nextLine !== null ? startLineNumber + 1 : ''}</div>
              <div>${this.lineContext.nextLine ? this.lineContext.nextLine : ''}</div>
            </div>
            ${this.lineContext.nextLine === null ? '' : '<div class="w-100 text-center pt-3">...</div>'}
          </pre>`,
          "",
          () => {
            this.callGenerateScript();
          },
          "xl",
        );
      } else {
        this.callGenerateScript();
      }

      await this.$nextTick();
    },

    callGenerateScript() {
      const params = {
        promptSessionId: this.promptSessionId,
        sourceCode: this.sourceCode,
        startColumn: this.selection.startColumn,
        endColumn: this.selection.endColumn,
        startLineNumber: this.selection.startLineNumber,
        endLineNumber: this.selection.endLineNumber,
        language: this.language,
        prompt: this.prompt,
        nonce: this.currentNonce,
      };

      const url = "/package-ai/generateScript";

      ProcessMaker.apiClient
        .post(url, params)
        .then((response) => {
          if (response.data?.progress?.status === "running") {
            this.progress = response.data.progress;
            this.$emit("request-started", this.progress, this.$t("Generating"));
          }
        })
        .catch(() => {
          const errorMsg = this.$t("Replace error message");
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    },

    async cleanScript() {
      if (!this.sourceCode || this.sourceCode === "") {
        this.error = this.$t("Please add and select some code to clean.");
        return;
      }
      this.getSelection();
      this.getNonce();
      this.$emit("set-diff", true);
      this.$emit("set-action", "clean");

      await this.$nextTick();

      const params = {
        promptSessionId: this.promptSessionId,
        sourceCode: this.sourceCode,
        startColumn: this.selection.startColumn,
        endColumn: this.selection.endColumn,
        startLineNumber: this.selection.startLineNumber,
        endLineNumber: this.selection.endLineNumber,
        language: this.language,
        nonce: this.currentNonce,
      };

      const url = "/package-ai/cleanScript";

      ProcessMaker.apiClient
        .post(url, params)
        .then((response) => {
          if (response.data?.progress?.status === "running") {
            this.progress = response.data.progress;
            this.$emit("request-started", this.progress, this.$t("Cleaning"));
          }
        })
        .catch((error) => {
          const errorMsg = this.$t("Replace error message");
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    },

    async documentScript() {
      if (!this.sourceCode || this.sourceCode === "") {
        this.error = this.$t("Please add and select some code to document.");
        return;
      }
      this.getSelection();
      this.getNonce();
      this.$emit("set-diff", true);
      this.$emit("set-action", "document");

      await this.$nextTick();

      const params = {
        promptSessionId: this.promptSessionId,
        sourceCode: this.sourceCode,
        startColumn: this.selection.startColumn,
        endColumn: this.selection.endColumn,
        startLineNumber: this.selection.startLineNumber,
        endLineNumber: this.selection.endLineNumber,
        language: this.language,
        nonce: this.currentNonce,
      };

      const url = "/package-ai/documentScript";

      ProcessMaker.apiClient
        .post(url, params)
        .then((response) => {
          if (response.data?.progress?.status === "running") {
            this.progress = response.data.progress;
            this.$emit(
              "request-started",
              this.progress,
              this.$t("Documenting"),
            );
          }
        })
        .catch(() => {
          const errorMsg = this.$t("Replace error message");
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    },
    async explainScript() {
      if (!this.sourceCode || this.sourceCode === "") {
        this.error = this.$t("Please add and select some code to explain.");
        return;
      }
      this.getSelection();
      this.getNonce();
      this.$emit("set-diff", false);
      this.$emit("set-action", "explain");

      await this.$nextTick();

      const params = {
        promptSessionId: this.promptSessionId,
        sourceCode: this.sourceCode,
        startColumn: this.selection.startColumn,
        endColumn: this.selection.endColumn,
        startLineNumber: this.selection.startLineNumber,
        endLineNumber: this.selection.endLineNumber,
        language: this.language,
        nonce: this.currentNonce,
      };

      const url = "/package-ai/explainScript";

      ProcessMaker.apiClient
        .post(url, params)
        .then((response) => {
          if (response.data?.progress?.status === "running") {
            this.progress = response.data.progress;
            this.$emit(
              "request-started",
              this.progress,
              this.$t("Generating explanation"),
            );
          }
        })
        .catch(() => {
          const errorMsg = this.$t("Replace error message");
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    },
  },
};
</script>
<style>
.script-toggle {
  cursor: pointer;
  user-select: none;
  background: #f7f7f7;
}

.code-preview {
  background: #e7f3fa;
  padding: 4px;
  border-radius: 8px;
}

.line-number-preview {
  min-width: 38px;
  text-align: center;
  border-right: 1px solid;
  margin-right: 7px;
  color: #1076d2;
  font-weight: 600;
  font-size: 115%;
}

.cursor-preview {
  font-size: 120%;
  margin-top: -1px;
  width: 7px;
  margin-left: -4px;
}

.accordion-icon {
  transition: all 200ms;
}

.collapsed .accordion-icon {
  transform: rotate(-90deg);
}

.ai-button-container {
  height: 8rem;
}

.ai-button {
  border-radius: 8px;
  box-shadow: 0 0 8px 0px #ddd;
  padding-left: 0.5rem !important;
  padding-right: 0.5rem !important;
}
.ai-button:hover {
  background: #f5f5f5 !important;
}

.ai-icon {
  margin-left: -1px;
}
.bg-assistant-buttons {
  background: #f8f8f8;
}
.alert-error {
  background-color: #D3E1FC;
  border: 0;
  border-radius: 8px;
  color: #1C4193;
}
</style>
