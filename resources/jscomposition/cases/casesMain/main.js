import App from "./App.vue";
import { routes } from "./routes";
import { cases } from "./store";
import { getUserConfiguration } from "./api";

Vue.use(VueRouter);
Vue.globalStore.registerModule("core:cases", cases);

const router = new VueRouter({
  mode: "history",
  base: "/",
  routes,
});

const loadUserConfiguration = async () => {
  const response = await getUserConfiguration();

  Vue.globalStore.commit("core:cases/updateUserConfiguration", {
    user_id: response.user_id,
    ui_configuration: JSON.parse(response.ui_configuration),
  });
};

loadUserConfiguration().then(() => {
  new Vue({
    el: "#cases-main",
    router,
    components: {
      App,
    },
    render: (h) => h(App),
  });
});
