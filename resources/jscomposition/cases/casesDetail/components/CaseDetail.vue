<template>
  <Tabs
    :tab-default="tabDefault"
    :tabs="tabs"
  />
</template>

<script setup>
import { ref, onBeforeMount } from "vue";
import Tabs from "./Tabs.vue";
import TaskTable from "./TaskTable.vue";
import RequestTable from "./RequestTable.vue";
import TabHistory from "./TabHistory.vue";
import CompletedForms from "./CompletedForms.vue";
import TabFiles from "./TabFiles.vue";
import Overview from "./Overview.vue";
import TabSummary from "./TabSummary.vue";
import { getRequestCount, getRequestStatus } from "../variables/index";

const translate = ProcessMaker.i18n;

const tabDefault = ref("tasks");

const tabs = [
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
    show: getRequestStatus() !== 'ERROR',
    content: TabSummary,
  },
  {
    name: translate.t("Completed & Form"),
    href: "#completed_form",
    current: "completed",
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

const urlTabs = [
  "tasks",
  "overview",
  "summary",
  "completed_form",
  "file_manager",
  "history",
  "requests",
];

const checkURL = () => {
  const hash = window.location.hash.substring(1);
  if (urlTabs.includes(hash)) {
    tabDefault.value = hash;
  }
};

onBeforeMount(() => {
  checkURL();
});

</script>
