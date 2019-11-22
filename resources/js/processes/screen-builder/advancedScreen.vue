<template>
  <div class="h-100">
    <monaco-editor :options="monacoOptions" v-model="code" language="html" class="editor"></monaco-editor>
  </div>
</template>

<script>
import MonacoEditor from "vue-monaco";

export default {
  props: ["process", "screen", "permission"],
  data() {
    return {
      monacoOptions: {
        automaticLayout: true,
      },
      code: "",
      showPopup: false
    };
  },
  components: {
    MonacoEditor
  }
};
</script>

<style lang="scss" scoped>
  .editor {
    min-height: 200px;
    height: 100%;
  }
</style>
