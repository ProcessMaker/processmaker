import * as VueRouter from "vue-router";
import { createRouter, createWebHistory } from "vue-router";

const router = createRouter({
  history: createWebHistory(),
  routes: [],
});

export default {
  global: {
    VueRouter,
  },
  pm: {
    Router: router,
  },
  use: {},
};
