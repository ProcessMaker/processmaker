<template>
  <b-container class="h-100">
    <b-card no-body class="h-100">
      <b-card-body class="overflow-hidden p-4">
        <b-row class="h-100">
          <b-col cols="12" class="h-100">
            <monaco-editor
                :options="monacoOptions"
                v-model="code"
                :language="language"
                class="h-100"
                :class="{hidden: resizing}"
            />
          </b-col>
        </b-row>
      </b-card-body>

      <b-card-footer class="d-flex">
        <span class="text-secondary text-sm">
          Language:
          <span class="text-uppercase">{{ language }}</span>
        </span>
      </b-card-footer>
    </b-card>
  </b-container>
</template>

<script>

import { debounce } from "lodash";
import MonacoEditor from "vue-monaco";
import MenuScript from "../../../components/Menu";

export default {
  props: ["process", "script", "scriptExecutor", "testData"],
  data() {
    return {
      executionKey: null,
      resizing: false,
      monacoOptions: {
        automaticLayout: true,
        readOnly: true,
      },
      code: this.script.code,
      preview: {
        error: {
          exception: '',
          message: ''
        },
        executing: false,
        data: this.testData ? this.testData : "{}",
        config: "{}",
        output: "",
        success: false,
        failure: false
      },
      outputOpen: true,
      optionsMenu: [],
    };
  },
  components: {
    MenuScript,
    MonacoEditor,
  },
  computed: {
    language() {
      return this.scriptExecutor.language;
    }
  },
  mounted() {
    ProcessMaker.EventBus.$emit("script-builder-init", this);
    window.addEventListener("resize", this.resizeHandler);
    let userID = document.head.querySelector('meta[name="user-id"]');
  },
  beforeDestroy: function() {
    window.removeEventListener("resize", this.resizeHandler);
  },

  methods: {
    stopResizing: debounce(function() {
      this.resizing = false;
    }, 50),
    resizeHandler() {
      this.resizing = true;
      this.stopResizing();
    },
    onClose() {
      window.location.href = "/designer/scripts";
    },
  }
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

.monaco-editor .gutter-insert, .monaco-diff-editor .gutter-insert {
  background-color: #c7f8d3 !important;
}

.cldr.delete-sign.codicon.codicon-diff-remove {
  background: #fbc7c7 !important;
}

.monaco-diff-editor .line-insert, .monaco-diff-editor .char-insert, .monaco-editor .margin-view-overlays .cldr {
  background: #e6ffec !important;
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

</style>
