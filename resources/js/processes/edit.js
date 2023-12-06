import Vue from "vue";
import CategorySelect from "./categories/components/CategorySelect.vue";
import ProcessTranslationListing from "./translations/components/ProcessTranslationListing.vue";
import CreateProcessTranslationModal from "./translations/components/CreateProcessTranslationModal.vue";

Vue.component("CategorySelect", CategorySelect);
Vue.component("ProcessTranslationListing", ProcessTranslationListing);
Vue.component("CreateProcessTranslationModal", CreateProcessTranslationModal);
