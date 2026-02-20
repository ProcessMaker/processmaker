import { createApp } from 'vue';
import ScriptExecutorsNew from "./ScriptExecutorsNew";
import I18NextVue from "i18next-vue";

const App = {
  data() {
    return {
    }
  },
  components: {
    ScriptExecutorsNew
  },
  template: `
    <div class="card card-body">
        <script-executors-new/>
    </div>
  `
}
createApp(App).use(I18NextVue, { i18next: window.ProcessMaker.i18n }).mount('#script-executors');
