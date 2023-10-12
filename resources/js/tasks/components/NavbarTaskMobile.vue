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
        :disabled="!existPrev"
        @click="goPrevNext('Prev')"
      >
        <i class="fas fa-chevron-left mr-1" />
        {{ $t('Prev') }}
      </button>
      <button
        type="buttom"
        class="dropleft btn btn-primary text-capitalize"
        :disabled="!existNext"
        @click="goPrevNext('Next')"
      >
        {{ $t('Next') }}
        <i class="fas fa-chevron-right ml-1" />
      </button>
    </div>
    <div>
      <button
        type="buttom"
        class="dropleft btn btn-primary"
        data-toggle="modal"
        data-target="#detailsTaskModal"
      >
        <i class="fas fa-info-circle" />
      </button>
      <task-details-mobile
        :task="task"
        :userisadmin="userisadmin"
        :userisprocessmanager="userisprocessmanager"
      />
    </div>
  </div>
</template>

<script>
import TaskDetailsMobile from "./TaskDetailsMobile.vue";

Vue.component("TaskDetailsMobile", TaskDetailsMobile);

export default {
  props: ["task", "userisadmin", "userisprocessmanager"],
  data() {
    return {
      data: this.$cookies.get("tasksListMobile"),
      prevTask: -1,
      nextTask: -1,
      existPrev: false,
      existNext: false,
    };
  },
  mounted() {
    const indexTask = this.data.indexOf(this.task.id);
    if ((indexTask - 1) >= 0) {
      this.existPrev = true;
      this.prevTask = this.data[indexTask - 1];
    }
    if ((indexTask + 1) < this.data.length) {
      this.existNext = true;
      this.nextTask = this.data[indexTask + 1];
    }
  },
  methods: {
    returnTasks() {
      window.location = "/tasks";
    },
    goPrevNext(action) {
      if (action === "Next" && this.existNext) {
        window.location = `/tasks/${this.nextTask}/edit`;
      }
      if (action === "Prev" && this.existPrev) {
        window.location = `/tasks/${this.prevTask}/edit`;
      }
    },
  },
};
</script>
