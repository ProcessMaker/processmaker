import Vue from "vue";
import Editor from "@tinymce/tinymce-vue";
import SiteDesign from "./components/SiteDesign.vue";
import ColorPicker from "./components/ColorPicker.vue";
import "tinymce/tinymce";
import "tinymce/themes/silver";
import "tinymce/icons/default";
import "tinymce/plugins/link";
import "tinymce/plugins/lists";
import "tinymce/plugins/code";
// skin: false skips TinyMCE's default skin fetch; Vite does not expose node_modules CSS URLs.
import "tinymce/skins/ui/oxide/skin.min.css";

Vue.component("SiteDesign", SiteDesign);
Vue.component("ColorPicker", ColorPicker);
Vue.component("Editor", Editor);