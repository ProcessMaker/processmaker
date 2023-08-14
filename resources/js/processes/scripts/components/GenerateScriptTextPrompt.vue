<template>
  <div class="p-2">
    <div class="p-2 d-flex justify-content-between">
      <div>{{ $t("Description:") }}</div>
      <div class="text-muted" data-test="token-count">
        {{ tokens + "/" + maxTokens + " " + $t("tokens") }}
      </div>
    </div>
    <div class="p-2">
      <b-form-textarea
        id="textarea"
        data-test="prompt-area"
        v-model="text"
        placeholder="Enter your prompt!..."
        rows="4"
        max-rows="4"
        :formatter="textFormatter"
      ></b-form-textarea>
    </div>
    <div class="d-flex p-2">
      <b-btn
        class="p-1 ml-auto"
        variant="success"
        data-test="generate-prompt-button"
        @click="generateScript()"
      >
        {{ $t("Generate") }}
      </b-btn>
    </div>
  </div>
</template>

<script>
export default {
  name: "GenerateScriptTextPrompt",
  data() {
    return {
      text: "",
      tokens: 0,
      maxTokens: 1000,
    };
  },
  watch: {
    text(newText) {
      this.tokens = newText.length;
    },
  },
  methods: {
    generateScript() {
      this.$emit("generate-script", this.text);
    },
    textFormatter(text) {
      return String(text).length <= this.maxTokens
        ? text
        : text.substring(0, text.length - 1);
    },
  },
};
</script>