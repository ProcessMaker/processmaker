<template>
  <div>
    <b-sidebar
      id="tasks-preview"
      ref="tasks-preview"
      v-model="showPreview"
      :right="showRight"
      shadow
      no-header
    >
      <template #default="{ hide }">
        <div class="p-3">
          <div class="d-flex w-100 h-100 mb-3">
            <div class="my-1">
              <a class="lead text-secondary font-weight-bold">
                {{ task.element_name }}
              </a>
            </div>
            <div class="ml-auto mr-0 text-right">
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Previous Tasks')"
                @click="goPrevNext('Prev')"
                :disabled="!existPrev"
              >
                <i class="fas fa-chevron-left"></i>
                {{ $t("Prev") }}
              </b-button>
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Next Tasks')"
                @click="goPrevNext('Next')"
                :disabled="!existNext"
              >
                {{ $t("Next") }}
                <i class="fas fa-chevron-right"></i>
              </b-button>
              <a class="text-secondary">|</a>
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Open Task')"
                :href="openTask()"
              >
                <i class="fas fa-external-link-alt"></i>
              </b-button>
              <a class="text-secondary">|</a>
              <b-button
                class="btn-light text-secondary"
                :aria-label="$t('Close')"
                @click="hide"
              >
                <i class="fas fa-times"></i>
              </b-button>
            </div>
          </div>
          <div>
            <b-embed
              type="iframe"
              :src="linkTasks"
            />
          </div>
        </div>
      </template>
    </b-sidebar>
  </div>
</template>

<script>
export default {
  data() {
    return {
      showPreview: false,
      showRight: true,
      linkTasks: "",
      task: {},
      data: [],
      prevTask: {},
      nextTask: {},
      existPrev: false,
      existNext: false,
    };
  },
  methods: {
    /**
     * Show the sidebar
     */
    showSideBar(info, data) {
      this.task = info;
      this.linkTasks = `/tasks/${info.id}/edit/preview`;
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
      for (let task in this.data) {
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
      if (action === "Next") {
        this.showSideBar(this.nextTask, this.data);
      }
      if (action === "Prev") {
        this.showSideBar(this.prevTask, this.data);
      }
    },
  },
};
</script>

<style>
#tasks-preview {
  top: 11%;
  width: 50%;
}
</style>
