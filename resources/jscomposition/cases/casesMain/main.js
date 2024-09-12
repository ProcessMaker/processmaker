
import App from './App.vue'
import { routes } from './routes';

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  base: "/",
  routes
});

new Vue({
    el: "#cases-main",
    router,
    components:{
        App
    },
    render: (h) => h(App),
})
