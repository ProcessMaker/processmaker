<template>
  <div>
    <div
    id="tasks-preview"
    ref="tasks-preview"
    v-if="showPreview"
    >
    <div 
      id="resizer"
      ref="resizer"
    >
      <i class='fas fa-grip-lines-vertical text-secondary'></i>
    </div>
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
      loading: true,
    };
  },
  updated() {
    document.getElementById("resizer").addEventListener("mousedown", (event) => {
        document.addEventListener("mousemove", this.resize, false);
        document.addEventListener("mouseup", () => {
          document.removeEventListener("mousemove", this.resize, false);
        }, false);
      });
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
    resize(e) {
      const size = `${e.x}px`;
      document.getElementById("tasks-preview").style.flexBasis = size;
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
#tasks-preview {
  top: 0;
  right: 0;
  width: 50%;
  height: 100%;
  position: absolute;
  background: white;
  min-width: 40%;
  box-shadow: -2px 0px 5px grey;
}
#resizer {
  float: left;
  flex-basis: 18px;
  z-index: 2;
  height: 100%;
  width: 10px;
  position: relative;
  cursor: col-resize;
  background: lightgrey;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}
.loadingFrame {
  opacity: 0.6;
}
</style>
