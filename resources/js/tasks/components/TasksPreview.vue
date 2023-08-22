<template>
  <div>
    <splitpanes
      id="splitpane"
      class="default-theme" 
      v-if="showPreview"
    >
      <pane style="opacity: 0;">
        <div></div>
      </pane>
      <pane min-size="40" max-size="99">
        <div
          id="tasks-preview"
          ref="tasks-preview"
          class="h-100"
        >
          <template>
            <div class="ml-2 p-3">
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
              <div>
                <div
                  v-show="loading"
                  class="text-center"
                >
                  <b-spinner />
                </div>
                <b-embed
                  v-show="!loading"
                  id="tasksFrame"
                  type="iframe"
                  :class="loading ? 'loadingFrame' : ''"
                  :src="linkTasks"
                  @load="frameLoaded"
                />
              </div>
            </div>
          </template>
        </div>
      </pane>
    </splitpanes>
  </div>
</template>

<script>
import { Splitpanes, Pane } from 'splitpanes'
import 'splitpanes/dist/splitpanes.css'

export default {
  components: { Splitpanes, Pane },
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
      loading: true,
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
    onClose(){
      this.showPreview = false;
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
    },
  },
};
</script>

<style>
#splitpane {
  top: 0;
  width: 100%;
  height: 100%;
  position: absolute;
}
.loadingFrame {
  opacity: 0.6;
}
</style>
