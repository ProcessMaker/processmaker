<template>
  <div class="p-2">
    <div class="p-2 d-flex justify-content-between">
      <div>{{ $t("Description:") }}</div>
      <div>
        {{ tokens + "/" + maxTokens + " " + $t("tokens") }}
      </div>
    </div>
    <div class="p-2">
      <b-form-textarea
        id="textarea"
        v-model="text"
        placeholder="Enter your prompt!..."
        rows="3"
        max-rows="6"
        :formatter="textFormatter"
      ></b-form-textarea>
    </div>
    <div class="p-2">
      <b-btn class="p-1 ml-auto" variant="success" @click="generateScript()">
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