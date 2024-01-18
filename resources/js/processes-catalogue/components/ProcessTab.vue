<template>
  <div class="mt-3">
    <b-tabs
      content-class="text-style"
      lazy
    >
      <b-tab
        title="My Requests"
        active
      >
        <request-tab
          :currentUser="currentUser"
          :process="process"
        ></request-tab>
      </b-tab>
      <b-tab
        class="bg-white"
        title="My Tasks"
      >
        <task-tab
          :currentUser="currentUser"
          :process="process"
        ></task-tab>
      </b-tab>
    </b-tabs>
  </div>
</template>

<script>
import RequestTab from "./RequestTab.vue";
import TaskTab from "./TaskTab.vue";
export default {
  components: {
    RequestTab,
    TaskTab,
  },
  props: {
    currentUser: {
      type: Object,
    },
    process: {
      type: Object,
    },
  },
};
</script>
<style>
.text-style {
  margin-bottom: 10px;
  color: #556271;
}
</style>
