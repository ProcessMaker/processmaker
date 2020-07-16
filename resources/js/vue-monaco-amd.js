/**
 * Replace the default vue-monaco with one that uses AMD modules
 * so we don't have to edit every instance of vue-monaco and add
 * the amdRequire prop.
 * 
 * See https://github.com/egoist/vue-monaco#use-amd-version
 * 
 */
import MonacoEditor from '../../node_modules/vue-monaco';

export default {
  extends: MonacoEditor,
  props: {
    amdRequire: {
      default() { 
        return window.require;
      }
    }
  },
  mounted() {
    // Workaround for https://github.com/microsoft/monaco-editor/issues/1855
    const ro = new ResizeObserver(_.debounce(this.resize, 150));
    ro.observe(this.$el);
  },
  methods: {
    resize() {
      if (this.editor) {
        this.editor.layout();
      }
    }
  }
}