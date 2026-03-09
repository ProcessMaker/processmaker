import { createApp, configureCompat } from 'vue';
import ScriptExecutorsNew from "./ScriptExecutorsNew";
import I18NextVue from "i18next-vue";

configureCompat({
  MODE: 3,
});

const App = {
  components: { ScriptExecutorsNew },
  template: `<script-executors-new />`,
};

createApp(App).use(I18NextVue, { i18next: window.ProcessMaker.i18n }).mount('#script-executors');
