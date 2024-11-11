<template>
  <Tabs
    :tab-default="tabDefault"
    :tabs="tabs" />
</template>

<script setup>
import { ref } from "vue";
import Tabs from "./Tabs.vue";
import TaskTable from "./TaskTable.vue";
import RequestTable from "./RequestTable.vue";
import TabHistory from "./TabHistory.vue";
import CompletedForms from "./CompletedForms.vue";
import TabFiles from "./TabFiles.vue";
import Overview from "./Overview.vue";
import { getRequestCount } from "../variables/index";

const translate = ProcessMaker.i18n;

const tabDefault = ref("tasks");

const tabs = [
  {
    name: translate.t("Tasks"),
    href: "#tasks",
    current: "tasks",
    show: true,
    content: TaskTable,
    "data-test": "tab-case-tasks"
  },
  {
    name: translate.t("Overview"),
    href: "#overview",
    current: "overview",
    show: true,
    content: Overview,
    "data-test": "tab-case-overview"
  },
  {
    name: translate.t("Completed & Form"),
    href: "#completed_form",
    current: "completed",
    show: true,
    content: CompletedForms,
    "data-test": "tab-case-completed-form"
  },
  {
    name: translate.t("File Manager"),
    href: "#file_manager",
    current: "file_manager",
    show: true,
    content: TabFiles,
    "data-test": "tab-case-files"
  },
  {
    name: translate.t("History"),
    href: "#history",
    current: "history",
    show: true,
    content: TabHistory,
    "data-test": "tab-case-history"
  },
  {
    name: translate.t("Requests"),
    href: "#requests",
    current: "requests",
    show: getRequestCount() !== 1,
    content: RequestTable,
    "data-test": "tab-case-requests"
  },
];
</script>
