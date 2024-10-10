<template>
  <Tabs
    :tab-default="tabDefault"
    :tabs="tabs"
  />
</template>

<script>
import { defineComponent, ref } from "vue";
import Tabs from "./Tabs.vue";
import TaskTable from "./TaskTable.vue";
import RequestTable from "./RequestTable.vue";
import TabHistory from "./TabHistory.vue";
import CompletedForms from "./CompletedForms.vue";
import TabFiles from "./TabFiles.vue";

export default defineComponent({
  components: { Tabs },
  setup() {
    const translate = ProcessMaker.i18n;

    const tabDefault = ref("tasks");

    const isFileManager = () => window.ProcessMaker.caseFileManager !== 'undefined';

    const tabs = [
      {
        name: translate.t("Tasks"), href: "#tasks", current: "tasks", show: true, content: TaskTable,
      },
      {
        name: translate.t("Overview"), href: "#overview", current: "overview", show: true, content: "",
      },
      {
        name: translate.t("Completed & Form"), href: "#completed_form", current: "completed", show: true, content: CompletedForms,
      },
      {
        name: translate.t("File Manager"), href: "#file_manager", current: "file_manager", show: isFileManager(), content: TabFiles,
      },
      {
        name: translate.t("History"), href: "#history", current: "history", show: true, content: TabHistory,
      },
      {
        name: translate.t("Requests"), href: "#requests", current: "requests", show: true, content: RequestTable,
      },
    ];

    return {
      tabs,
      tabDefault,
    };
  },
});
</script>
