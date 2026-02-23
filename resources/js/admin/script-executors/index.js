import { createApp } from 'vue';
import ScriptExecutorsNew from "./ScriptExecutorsNew";
import I18NextVue from "i18next-vue";
// import {BApp} from 'bootstrap-vue-next'

const App = {
  data() {
    return {
    }
  },
  components: {
    BApp,
    ScriptExecutorsNew
  },
  template: `
    <div>
      <script-executors-new/>
    </div>
  `
}
createApp(App).use(I18NextVue, { i18next: window.ProcessMaker.i18n }).mount('#script-executors');
