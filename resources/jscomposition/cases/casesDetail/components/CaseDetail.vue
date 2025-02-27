<template>
  <Tabs
    :tab-default="tabDefault"
    :tabs="tabs"
    :keep-alive="['NewOverview']"
  />
</template>

<script setup>
import { computed } from "vue";
import Tabs from "./Tabs.vue";
import TaskTable from "./TaskTable.vue";
import RequestTable from "./RequestTable.vue";
import TabHistory from "./TabHistory.vue";
import CompletedForms from "./CompletedForms.vue";
import TabFiles from "./TabFiles.vue";
import Overview from "./NewOverview.vue";
import TabSummary from "./TabSummary.vue";
import ErrorsTab from "./ErrorsTab.vue";
import { getRequestCount, getRequestStatus, isErrors } from "../variables/index";

const translate = ProcessMaker.i18n;
const Router = window.ProcessMaker.Router;
const path = window.location.pathname;

const urlTabs = [
  "errors",
  "tasks",
  "overview",
  "summary",
  "completed_form",
  "file_manager",
  "history",
  "requests",
];

const tabDefault = computed(() => {
  if (urlTabs.includes(window.location.hash.substring(1))) {
    return window.location.hash.substring(1);
  }

  // This section is for the package-files, the issue should be fixed in page with vue-router
  const routeResolved = Router.resolve(path);
  if (routeResolved.route?.name && routeResolved.route?.meta?.package === "package-files") {
    return "file_manager";
  }
  if (isErrors()) {
    return "errors";
  }
  return "tasks";
});

const tabs = [
  {
    name: translate.t("Errors"),
    href: "#errors",
    current: "errors",
    show: isErrors(),
    content: ErrorsTab,
  },
  {
    name: translate.t("Tasks"),
    href: "#tasks",
    current: "tasks",
    show: true,
    content: TaskTable,
  },
  {
    name: translate.t("Overview"),
    href: "#overview",
    current: "overview",
    show: true,
    content: Overview,
  },
  {
    name: translate.t("Summary"),
    href: "#summary",
    current: "summary",
    show: getRequestStatus() !== "ERROR",
    content: TabSummary,
  },
  {
    name: translate.t("Completed & Form"),
    href: "#completed_form",
    current: "completed_form",
    show: true,
    content: CompletedForms,
  },
  {
    name: translate.t("File Manager"),
    href: "#file_manager",
    current: "file_manager",
    show: true,
    content: TabFiles,
  },
  {
    name: translate.t("History"),
    href: "#history",
    current: "history",
    show: true,
    content: TabHistory,
  },
  {
    name: translate.t("Requests"),
    href: "#requests",
    current: "requests",
    show: getRequestCount() !== 1,
    content: RequestTable,
  },
];
</script>
