import Vue from "vue";
import SiteDesign from "./components/SiteDesign";
import ColorPicker from "./components/ColorPicker";
import Editor from '@tinymce/tinymce-vue'
import 'tinymce/themes/silver';
import 'tinymce/plugins/link';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/code';

Vue.component('site-design', SiteDesign);
Vue.component('color-picker', ColorPicker);
Vue.component('editor', Editor);
