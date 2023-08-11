<template>
  <div class="card-header m-0 d-flex border-0 pb-1">
    <div>
      <div>{{ $t("Description:") }}</div>
      <div align-self="end" cols="1" class="mr-2">
        {{ tokens + "/" + maxTokens + " " + $t("tokens") }}
      </div>
    </div>
    <div>
      <b-form-textarea
        id="textarea"
        v-model="text"
        placeholder="Enter your prompt!..."
        rows="3"
        max-rows="6"
        :formatter="textFormatter"
      ></b-form-textarea>
    </div>
    <div align-self="end" cols="1" class="mr-2">
      <b-btn class="p-1" variant="success" @click="generateScript()">
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