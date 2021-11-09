import Vue from "vue";
import Router from "vue-router";

import Layout from "./layouts/MainLayout.vue";

Vue.use(Router);

let router = new Router({
  mode: "history",
  base: "/admin/queues",
  routes: [
    {
      path: "/",
      redirect: "/dashboard",
    },
    {
      path: "/dashboard",
      component: () => import("/vendor/laravel/horizon/pages/Dashboard.vue"),
    },
    {
      path: "/monitoring",
      component: () => import("/vendor/laravel/horizon/pages/Monitoring/Index.vue"),
    },
    {
      path: "/monitoring/:tag",
      component: () => import("/vendor/laravel/horizon/pages/Monitoring/Tag.vue"),
      children: [
        {
          path: "/",
          name: "monitoring.detail.index",
          component: () => import("/vendor/laravel/horizon/pages/Monitoring/Jobs.vue"),
          props: {type: "index"},
        },
        {
          path: "failed",
          name: "monitoring.detail.failed",
          component: () => import("/vendor/laravel/horizon/pages/Monitoring/Jobs.vue"),
          props: {type: "failed"},
        },
      ],
    },
    {
      path: "/metrics",
      component: () => import("/vendor/laravel/horizon/pages/Metrics/Index.vue"),
      children: [
        {
          path: "/",
          redirect: "jobs",
        },
        {
          path: "jobs",
          component: () => import("/vendor/laravel/horizon/pages/Metrics/Jobs.vue"),
        },
        {
          path: "queues",
          component: () => import("/vendor/laravel/horizon/pages/Metrics/Queues.vue"),
        },
      ],
    },
    {
      path: "/metrics/:type/:slug",
      name: "metrics.detail",
      component: () => import("/vendor/laravel/horizon/pages/Metrics/Metric.vue"),
      props: true,
    },
    {
      path: "/recent-jobs",
      name: "recent-jobs.detail",
      component: () => import("/vendor/laravel/horizon/pages/RecentJobs/Index.vue"),
    },
    {
      path: "/failed",
      component: () => import("/vendor/laravel/horizon/pages/Failed/Index.vue"),
    },
    {
      path: "/failed/:jobId",
      name: "failed.detail",
      component: () => import("/vendor/laravel/horizon/pages/Failed/Job.vue"),
      props: true,
    },
  ],
});

for (let index = 0; index < router.options.routes.length; index++) {
  let route = router.options.routes[index];
  if (route.component) {
    if (route.component.components && route.component.components.Layout) {
      // It has a layout, so let's replace it with *our* layout
      route.component.components.Layout = Layout;
    }
  }
}

export default router;
