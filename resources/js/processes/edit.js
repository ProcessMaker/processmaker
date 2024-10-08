import CategorySelect from "./categories/components/CategorySelect";
import ProcessesPermissions from "./components/ProcessesPermissions.vue";
import ProcessTranslationListing from "./translations/components/ProcessTranslationListing";
import CreateProcessTranslationModal from "./translations/components/CreateProcessTranslationModal";

Vue.component("CategorySelect", CategorySelect);
Vue.component("ProcessTranslationListing", ProcessTranslationListing);
Vue.component("CreateProcessTranslationModal", CreateProcessTranslationModal);
Vue.component("ProcessesPermissions", ProcessesPermissions);