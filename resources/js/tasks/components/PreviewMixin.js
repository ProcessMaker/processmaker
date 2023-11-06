const PreviewMixin = {
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
      this.resetToDefault();
    },
    resetToDefault() {
      this.linkTasks1 = "";
      this.linkTasks2 = "";
      this.task = {};
      this.data = [];
      this.taskTitle = "";
      this.prevTask = {};
      this.nextTask = {};
      this.existPrev = false;
      this.existNext = false;
      this.loading = true;
      this.paneMinSize = 0;
      this.showFrame = 1;
      this.showFrame1 = false;
      this.showFrame2 = false;
      this.isLoading = "";
      this.stopFrame = false;
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
      }, 4900);

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
      this.stopFrame = false;
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

export default PreviewMixin;
