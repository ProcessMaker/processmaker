import Vue from "vue";
import Router from "vue-router";
import TemplateAssetsView from "../components/templates/TemplateAssetsView.vue";

Vue.use(Router);

const router = new Router({
  mode: "history",
  routes: [
    { path: "/template/assets", name: "choose-template-assets", component: TemplateAssetsView },
    // { path: "/existing-assets", name: "choose-template-assets", component: TemplateAssetsView, redirect:"template/assets", props: { assets: [] } },
  ],
});

router.beforeEach((to, from, next) => {
  console.log('to', to);
  console.log('from', from);
  // console.log('next', next);
  // const assets = to.meta.assets;
  next();
  // next({ params: to.params });
  // return { path: to.path, force: true, params: to.params };
  // }
});

export default router;
