<template>
  <div>
    <b-list-group-item class="script-toggle border-0 mb-0">
      <b-row v-b-toggle.assistant data-test="cornea-tab-toggle">
        <b-col v-if="!showPromptArea">
          <img class="mb-1" :src="corneaIcon" />
          {{ $t("Cornea AI Assistant") }}
        </b-col>
        <b-col
          data-test="arrow-generate-back"
        >
          <i v-if="!showPreview" class="mr-2 fa fa-arrow-left" @click="showPromptArea = false" />
          {{ $t("Generate Script From Text") }}
        </b-col>
        <b-col v-if="!showPromptArea" class="p-0 text-right" cols="3">
          <span
            class="text-center px-2 bg-warning rounded small"
          >
            {{ $t("NEW") }}
          </span>
        </b-col>
        <b-col v-if="!showPreview" align-self="end" cols="1" class="mr-2">
          <i class="fas fa-chevron-down accordion-icon" />
        </b-col>
      </b-row>
    </b-list-group-item>
    <b-list-group-item
      class="p-0 border-left-0 border-right-0 border-top-0 mb-0"
    >
      <b-collapse id="assistant" :visible="showPromptArea">
        <div v-if="!showPromptArea">
          <div class="card-header m-0 d-flex border-0 pb-1">
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
                data-test="generate-script-btn"
                @click="showPromptArea = true"
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

          <div class="card-header m-0 d-flex border-0 pt-0">
            <div class="d-flex w-50 p-2 ai-button-container">
              <div
                role="button"
                class="d-flex align-items-center flex-column bg-light ai-button w-100 py-4 justify-content-center"
                data-test="clean-script-btn"
                @click="cleanScript()"
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
          v-else
          :showPreview="showPreview"
          :prompt-session-id="promptSessionId"
          @generate-script="onGenerateScript"
        />
      </b-collapse>
    </b-list-group-item>
  </div>
</template>
<script>
import GenerateScriptTextPrompt from "./GenerateScriptTextPrompt.vue";

export default {
  name: "CorneaTab",
  components: {
    GenerateScriptTextPrompt,
  },
  props: ["user", "sourceCode", "language", "selection", "packageAi", "showPreview"],
  data() {
    return {
      showPromptArea: false,
      corneaIcon: require("./../../../../img/cornea_icon.svg"),
      penSparkleIcon: require("./../../../../img/pen_sparkle_icon.svg"),
      bookIcon: require("./../../../../img/book_icon.svg"),
      brushIcon: require("./../../../../img/brush_icon.svg"),
      listIcon: require("./../../../../img/list_icon.svg"),
      changesApplied: false,
      newCode: `\n $a = 3+4; \n $b = $a / 2;`,
      loading: false,
      promptSessionId: "",
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
  },

  methods: {
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
            window.ProcessMaker.alert(errorMsg, "danger");
          }
        });
    },
    onGenerateScript(prompt) {
      this.prompt = prompt;
      this.generateScript();
    },
    async generateScript() {
      this.getSelection();
      this.getNonce();
      this.$emit("set-diff", true);

      await this.$nextTick();

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
        .catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    },

    async cleanScript() {
      this.getSelection();
      this.getNonce();
      this.$emit("set-diff", true);

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
          const errorMsg = error.response?.data?.message || error.message;
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    },

    async documentScript() {
      this.getSelection();
      this.getNonce();
      this.$emit("set-diff", true);

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
            this.$emit("request-started", this.progress, this.$t("Documenting"));
          }
        })
        .catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    },
    async explainScript() {
      this.getSelection();
      this.getNonce();
      this.$emit("set-diff", false);

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
            this.$emit("request-started", this.progress, this.$t("Generating explanation"));
          }
        })
        .catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    }
  },
};
</script>
<style>
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

.ai-button-container {
  height: 8rem;
}

.ai-button {
  border-radius: 8px;
  box-shadow: 0 0 8px 0px #ddd;
}
.ai-button:hover {
  background: #f5f5f5 !important;
}
</style>