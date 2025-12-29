import { createApp } from 'vue';
import { createI18n } from 'vue-i18n'
import ScriptExecutors from "./ScriptExecutors";

const i18n = createI18n({
  locale: 'en',
  messages: {
    en: {
      hello: 'Hello, world!'
    }
  }
})

const App = {
  data() {
    return {
    }
  },
  components: {
    ScriptExecutors
  },
  template: `
    <div class="card card-body">
        <script-executors/>
    </div>
  `
}
createApp(App).use(i18n).mount('#script-executors');
