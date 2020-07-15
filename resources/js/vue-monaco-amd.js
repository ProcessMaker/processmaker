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
  ...MonacoEditor,
  props: {
    ...MonacoEditor.props,
    amdRequire: {
      default() { 
        return window.require;
      }
    }
  },
};