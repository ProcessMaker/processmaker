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
      component: () => import("@horizon/screens/dashboard.vue"),
    },
    {
      path: "/monitoring",
      component: () => import("@horizon/screens/monitoring/index.vue"),
    },
    {
      path: "/monitoring/:tag",
      component: () => import("@horizon/screens/monitoring/tag.vue"),
      children: [
        {
          path: "/",
          name: "monitoring.detail.index",
          component: () => import("@horizon/screens/monitoring/tag-jobs.vue"),
          props: {type: "index"},
        },
        {
          path: "failed",
          name: "monitoring.detail.failed",
          component: () => import("@horizon/screens/monitoring/tag-jobs.vue"),
          props: {type: "failed"},
        },
      ],
    },
    {
      path: "/metrics",
      component: () => import("@horizon/screens/metrics/index.vue"),
      children: [
        {
          path: "/",
          redirect: "jobs",
        },
        {
          path: "jobs",
          component: () => import("@horizon/screens/metrics/jobs.vue"),
        },
        {
          path: "queues",
          component: () => import("@horizon/screens/metrics/queues.vue"),
        },
      ],
    },
    {
      path: "/metrics/:type/:slug",
      name: "metrics.detail",
      component: () => import("@horizon/screens/metrics/preview.vue"),
      props: true,
    },
    {
      path: "/recent-jobs",
      name: "recent-jobs.detail",
      component: () => import("@horizon/screens/recentJobs/index.vue"),
    },
    {
      path: "/failed",
      component: () => import("@horizon/screens/failedJobs/index.vue"),
    },
    {
      path: "/failed/:jobId",
      name: "failed.detail",
      component: () => import("@horizon/screens/failedJobs/job.vue"),
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
