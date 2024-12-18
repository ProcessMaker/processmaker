import Router from "vue-router";

export default {
  global: {
    VueRouter: Router,
  },
  pm: {
    Router: new Router({
      mode: "history",
    }),
  },
  use: {
    Router,
  },
};
