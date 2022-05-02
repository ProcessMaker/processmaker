import Vue from "vue";
import Editor from "@tinymce/tinymce-vue";
import SiteDesign from "./components/SiteDesign";
import ColorPicker from "./components/ColorPicker";
import "tinymce/themes/silver";
import "tinymce/plugins/link";
import "tinymce/plugins/lists";
import "tinymce/plugins/code";

Vue.component("SiteDesign", SiteDesign);
Vue.component("ColorPicker", ColorPicker);
Vue.component("Editor", Editor);
