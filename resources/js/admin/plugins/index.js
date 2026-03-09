import { createApp, configureCompat } from 'vue';
import PluginsIndex from './PluginsIndex.vue';
import I18NextVue from 'i18next-vue';

configureCompat({
  MODE: 3,
});

const App = {
  components: { PluginsIndex },
  template: `<plugins-index />`,
};

createApp(App)
  .use(I18NextVue, { i18next: window.ProcessMaker.i18n })
  .mount('#plugins-app');
