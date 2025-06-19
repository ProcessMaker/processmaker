import Vue from "vue";
import VueRouter from "vue-router";
import TenantQueuesDashboard from "./TenantQueuesDashboard.vue";
import JobDetails from "./JobDetails.vue";
import TenantJobs from "./TenantJobs.vue";

Vue.use(VueRouter);

const routes = [
  {
    path: "/",
    name: "dashboard",
    component: TenantQueuesDashboard,
    meta: { title: "Tenant Jobs Dashboard" },
  },
  {
    path: "/tenant/:tenantId/jobs",
    name: "tenant-jobs",
    component: TenantJobs,
    props: true,
    meta: { title: "Tenant Jobs" },
  },
  {
    path: "/tenant/:tenantId/jobs/:jobId",
    name: "job-details",
    component: JobDetails,
    props: true,
    meta: { title: "Job Details" },
  },
  {
    path: "*",
    redirect: "/",
  },
];

const router = new VueRouter({
  mode: "hash",
  base: "/admin/tenant-queues/",
  routes,
});

// Update page title based on route meta
router.beforeEach((to, from, next) => {
  if (to.meta && to.meta.title) {
    document.title = `${to.meta.title} - ProcessMaker`;
  }
  next();
});

export default router;
