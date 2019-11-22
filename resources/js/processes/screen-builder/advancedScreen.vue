<template>
  <div class="h-100">
    <div class="p-2">
      <b-button-group size="sm">
        <b-button
          :variant="mode == 'editor' ? 'secondary' : 'outline-secondary'"
          @click="mode = 'editor'"
          class="text-capitalize"
        >
          <i class="fas fa-drafting-compass pr-1"></i>
          {{ $t('Design') }}
        </b-button>
        <b-button
          :variant="mode == 'preview' ? 'secondary' : 'outline-secondary'"
          @click="mode = 'preview'"
          class="text-capitalize"
        >
          <i class="fas fa-cogs pr-1"></i>
          {{ $t('Preview') }}
        </b-button>
      </b-button-group>
    </div>
    <monaco-editor
      v-show="mode == 'editor'"
      :options="monacoOptions"
      v-model="config.html"
      language="html"
      class="editor"
    ></monaco-editor>
    <advanced-screen-frame v-show="mode == 'preview'" :config="config"></advanced-screen-frame>
  </div>
</template>

<script>
import MonacoEditor from "vue-monaco";
import AdvancedScreenFrame from "./advancedScreenFrame";

export default {
  props: ["process", "screen", "permission"],
  data() {
    return {
      monacoOptions: {
        automaticLayout: true
      },
      config: { html: "" },
      mode: "editor",
      showPopup: false
    };
  },
  components: {
    MonacoEditor,
    AdvancedScreenFrame
  },
  watch: {
    screen: {
      deep: true,
      immediate: true,
      handler(screen) {
        screen.config ? (this.config = screen.config) : null;
      }
    }
  }
};
</script>

<style lang="scss" scoped>
.editor {
  min-height: 200px;
  height: 100%;
}
</style>
