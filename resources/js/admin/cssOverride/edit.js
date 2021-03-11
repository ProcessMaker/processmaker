import Vue from "vue";
import ColorPicker from "./components/ColorPicker";
import Editor from '@tinymce/tinymce-vue'
import 'tinymce/themes/silver';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/code';

Vue.component('color-picker', ColorPicker);
Vue.component('editor', Editor);
