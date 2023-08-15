<template>
  <div class="p-2">
    <div class="p-2 d-flex justify-content-between">
      <div>{{ $t("Description:") }}</div>
      <div class="text-muted" data-test="token-count">
        <span :class="{'text-danger': maxTokensExceeded}">{{ tokens }}</span>/{{ maxTokens }} {{ $t("tokens") }}
      </div>
    </div>
    <div class="p-2">
      <b-form-textarea
        id="textarea"
        ref="textArea"
        data-test="prompt-area"
        v-model="text"
        placeholder="Enter your prompt!..."
        rows="4"
        max-rows="4"
      ></b-form-textarea>
    </div>
    <div class="d-flex p-2 align-items-center">
      <div role="button" class="text-primary" @click="toggleSuggestions()">
        <i class="fa fa-lightbulb mr-2"></i>Give me inspiration!
      </div>
      <b-btn
        class="p-1 ml-auto"
        variant="success"
        data-test="generate-prompt-button"
        :disabled="maxTokensExceeded"
        @click="generateScript()"
      >
        {{ $t("Generate") }}
      </b-btn>
    </div>

    <suggestions
      :prompt-session-id="promptSessionId"
      :suggestions-pages="suggestionsPages"
      :loading="loadingSuggestions"
      :show-suggestions="showSuggestions"
      :parent-suggestions-height="parentSuggestionsHeight"
      :min-parent-suggestions-height="minParentSuggestionsHeight"
      @suggestion-applied="onSuggestionApplied"></suggestions>
  </div>
</template>

<script>
import _, { debounce } from "lodash";
import Suggestions from "./Suggestions";

export default {
  props: ["promptSessionId"],
  name: "GenerateScriptTextPrompt",
  components: {
    Suggestions,
  },
  data() {
    return {
      text: "",
      tokens: 0,
      maxTokens: 1000,
      showSuggestions: false,
      suggestionsPages: [],
      loadingSuggestions: true,
      parentSuggestionsHeight: 100,
      minParentSuggestionsHeight: 150,
      model: "gpt-3.5-turbo-16k",
    };
  },
  computed: {
    maxTokensExceeded() {
      return this.tokens > this.maxTokens;
    },
  },
  watch: {
    text() {
      if (this.text) {
        this.calculateTokens();
      } else {
        this.tokens = 0;
      }
    },
  },
  mounted() {
    this.fetchSuggestions();
  },
  methods: {
    toggleSuggestions() {
      this.showSuggestions = !this.showSuggestions;
    },
    generateScript() {
      this.$emit("generate-script", this.text);
    },
    calculateTokens: debounce(function () {
      const params = {
        model: this.model,
        prompt: this.text,
      };

      const url = "/package-ai/calculateTokens";

      ProcessMaker.apiClient.post(url, params)
        .then((response) => {
          this.tokens = response.data.tokens;
        }).catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;
          window.ProcessMaker.alert(errorMsg, "danger");
        });
    }, 500),
    onSuggestionApplied(suggestion) {
      const cursorPosition = this.$refs.textArea.selectionStart;
      this.prompt = `${this.prompt.slice(0, cursorPosition)} ${suggestion} ${this.prompt.slice(cursorPosition)}`;
    },
    fetchSuggestions() {
      if (this.suggestionsPages.length) {
        return;
      }

      this.loadingSuggestions = true;

      if (!this.promptSessionId || this.promptSessionId === "") {
        return;
      }

      const params = {
        promptSessionId: this.promptSessionId,
        perPage: this.suggestionsPerPage,
      };

      const url = "/package-ai/getScriptPromptSuggestion";

      ProcessMaker.apiClient.post(url, params)
        .then((response) => {
          this.suggestionsPages = response.data.suggestions;
          this.loadingSuggestions = false;
        }).catch((error) => {
          const errorMsg = error.response?.data?.message || error.message;
          window.ProcessMaker.alert(errorMsg, "danger");
          this.loadingSuggestions = false;
        });
    },
  },
};
</script>