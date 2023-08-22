<template>
  <div>
    <b-sidebar
      id="tasks-preview"
      ref="tasks-preview"
      v-model="showPreview"
      :right="showRight"
      shadow
      lazy
      no-header
    >
      <template #default="{ hide }">
        <div class="p-3">
          <div class="d-flex w-100 h-100 mb-3">
            <div class="my-1">
              <a class="lead text-secondary font-weight-bold">
                {{ taskTitle }}
              </a>
            </div>
            <div class="ml-auto mr-0 text-right">
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Previous Tasks')"
                :disabled="!existPrev"
                @click="goPrevNext('Prev')"
              >
                <i class="fas fa-chevron-left" />
                {{ $t("Prev") }}
              </b-button>
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Next Tasks')"
                :disabled="!existNext"
                @click="goPrevNext('Next')"
              >
                {{ $t("Next") }}
                <i class="fas fa-chevron-right" />
              </b-button>
              <a class="text-secondary">|</a>
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Open Task')"
                :href="openTask()"
              >
                <i class="fas fa-external-link-alt" />
              </b-button>
              <a class="text-secondary">|</a>
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Close')"
                @click="hide"
              >
                <i class="fas fa-times" />
              </b-button>
            </div>
          </div>
          <div
            v-if="!stopFrame"
            class="frame-container"
          >
            <b-embed
              v-if="showFrame1"
              id="tasksFrame1"
              width="100%"
              :class="showFrame2 ? 'loadingFrame' : ''"
              :src="linkTasks1"
              @load="frameLoaded()"
            />
            <b-embed
              v-if="showFrame2"
              id="tasksFrame2"
              width="100%"
              :class="showFrame1 ? 'loadingFrame' : ''"
              :src="linkTasks2"
              @load="frameLoaded()"
            />
          </div>
          <div v-else>
            <task-loading />
          </div>
        </div>
      </template>
    </b-sidebar>
  </div>
</template>

<script>
import TaskLoading from "./TaskLoading.vue";

export default {
  components: { TaskLoading },
  data() {
    return {
      showPreview: false,
      showRight: true,
      linkTasks1: "",
      linkTasks2: "",
      task: {},
      data: [],
      taskTitle: "",
      prevTask: {},
      nextTask: {},
      existPrev: false,
      existNext: false,
      loading: true,
      showFrame: 1,
      showFrame1: false,
      showFrame2: false,
      isLoading: "",
      stopFrame: false,
    };
  },
  methods: {
    /**
     * Show the sidebar
     */
    showSideBar(info, data, firstTime = false) {
      this.stopFrame = false;
      this.taskTitle = info.element_name;
      this.showFrame1 = firstTime ? true : this.showFrame1;
      this.task = info;
      if (this.showFrame === 1) {
        this.linkTasks1 = `/tasks/${info.id}/edit/preview`;
        this.showFrame1 = true;
      }
      if (this.showFrame === 2) {
        this.showFrame2 = true;
        this.linkTasks2 = `/tasks/${info.id}/edit/preview`;
      }
      this.showPreview = true;
      this.data = data;
      this.existPrev = false;
      this.existNext = false;
      this.defineNextPrevTask();
    },
    /**
     * Defined Previuos and Next task
    */
    defineNextPrevTask() {
      let prevTask = {};
      let nextTask = {};
      let seeNextTask = false;
      for (const task in this.data) {
        if (!seeNextTask) {
          if (this.data[task] === this.task) {
            seeNextTask = true;
          } else {
            prevTask = this.data[task];
            this.existPrev = true;
          }
        } else {
          nextTask = this.data[task];
          this.existNext = true;
          break;
        }
      }
      this.prevTask = prevTask;
      this.nextTask = nextTask;
    },
    /**
     * Expand Open task
     */
    openTask() {
      return `/tasks/${this.task.id}/edit`;
    },
    /**
     * Go to previous or next task
     */
    goPrevNext(action) {
      // Init counter of 5 seconds
      this.isLoading = setTimeout(() => {
        this.stopFrame = true;
        this.taskTitle = this.$t("Task Lorem");
      }, 5000);

      this.stopFrame = false;
      this.linkTasks = "";
      this.loading = true;
      if (action === "Next") {
        this.showSideBar(this.nextTask, this.data);
      }
      if (action === "Prev") {
        this.showSideBar(this.prevTask, this.data);
      }
    },
    /**
     * Show the frame when this is loaded
     */
    frameLoaded() {
      this.loading = false;
      clearTimeout(this.isLoading);
      if (this.showFrame === 1) {
        this.showFrame1 = true;
        this.showFrame2 = false;
        this.showFrame = 2;
        return;
      }
      this.showFrame2 = true;
      this.showFrame1 = false;
      this.showFrame = 1;
    },
  },
};
</script>

<style>
#tasks-preview {
  top: 11%;
  width: 50%;
}
.loadingFrame {
  opacity: 0.5;
}
.frame-container {
  display: grid;
}
.embed-responsive {
  position: absolute;
  display: flex;
  width: 100%;
  padding: 0;
  overflow: hidden
  grid-row;
}
</style>
