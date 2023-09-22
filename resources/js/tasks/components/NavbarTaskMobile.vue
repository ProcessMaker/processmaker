<template>
  <div
    id="navbarTaskMobile"
    class="d-flex bg-primary p-2 justify-content-between"
  >
    <button
      type="buttom"
      class="dropleft btn btn-primary"
      @click="returnTasks()"
    >
      <i class="fas fa-arrow-left" />
    </button>
    <div>
      <button
        type="buttom"
        class="dropleft btn btn-primary text-capitalize"
      >
        <i class="fas fa-chevron-left mr-1" />
        {{ $t('Prev') }}
      </button>
      <button
        type="buttom"
        class="dropleft btn btn-primary text-capitalize"
      >
        {{ $t('Next') }}
        <i class="fas fa-chevron-right ml-1" />
      </button>
    </div>
    <task-details-mobile :task="task" />
  </div>
</template>

<script>
import TaskDetailsMobile from "./TaskDetailsMobile.vue";

Vue.component("TaskDetailsMobile", TaskDetailsMobile);

export default {
  props: ["task"],
  data() {
    return {};
  },
  methods: {
    returnTasks() {
      window.location = `/tasks`;
    },
  },
};
</script>
