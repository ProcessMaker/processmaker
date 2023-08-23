<template>
  <div>
    <splitpanes
      v-if="showPreview"
      id="splitpane"
      ref="inspectorSplitPanes"
      class="default-theme"
      :dbl-click-splitter="false"
    >
      <pane style="opacity: 0;">
        <div />
      </pane>
      <pane
        id="pane-task-preview"
        :min-size="paneMinSize"
        max-size="99"
        style="background-color: white;"
      >
        <div
          id="tasks-preview"
          ref="tasks-preview"
          class="h-100 p-3"
        >
          <div>
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
                  @click="onClose()"
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
        </div>
      </pane>
    </splitpanes>
  </div>
</template>

<script>
import { Splitpanes, Pane } from "splitpanes";
import TaskLoading from "./TaskLoading.vue";
import "splitpanes/dist/splitpanes.css";

export default {
  components: { Splitpanes, Pane, TaskLoading },
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
      paneMinSize: 0,
      showFrame: 1,
      showFrame1: false,
      showFrame2: false,
      isLoading: "",
      stopFrame: false,
    };
  },
  updated() {
    const resizeOb = new ResizeObserver((entries) => {
      const { width } = entries[0].contentRect;
      this.setPaneMinSize(width, 480);
    });
    resizeOb.observe(this.$refs.inspectorSplitPanes.container);
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
    onClose() {
      this.showPreview = false;
    },
    setPaneMinSize(splitpanesWidth, minPixelWidth) {
      this.paneMinSize = (minPixelWidth * 100) / splitpanesWidth;
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
#splitpane {
  top: 0;
  min-height: 100vh;
  width: 99%;
  position: absolute;
}
#pane-task-preview {
  flex-grow: 1;
  overflow-y: auto;
}
#tasks-preview {
  box-sizing: border-box;
  display: block;
}
.loadingFrame {
  opacity: 0.5;
}
.frame-container {
  display: grid;
}
.embed-responsive {
  display: flex;
  width: 100%;
  padding: 0;
  overflow: hidden
  grid-row;
}
</style>
