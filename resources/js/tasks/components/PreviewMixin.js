const PreviewMixin = {
  data() {
    return {
      isDisabled: true,
      tooltipFromButton: "",
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
      formData: {},
      options: {
        is_loading: false,
      },
      autoSaveDelay: 2000,
      savedIcon: null,
      lastAutosave: "",
      errorAutosave: false,
      showQuickFillPreview: false,
      isSelectedTask: false,
      selectedTaskId: null,
      useThisDataButton: false,
      showUseThisTask: false,
      splitpaneSize: 50,
      propColumns: [],
      propFilters: {},
      userHasInteracted: false,
      isPriority: false,
      size: 50,
      screenWidthPx: 0,
      ellipsisButton: false,
      selectedUser: [],
      showReassignment: false,
      taskDefinition: {},
      user: {},
      actions: [
        {
          value: "clear-draft",
          content: "Clear Draft",
          image: "/img/smartinbox-images/eraser.svg",
        },
        {
          value: "quick-fill",
          content: "Quick Fill",
          image: "/img/smartinbox-images/fill.svg",
        },
        {
          value: "mark-priority",
          content: "Mark as Priority",
          image: "/img/priority-header.svg",
        },
        {
          value: "open-task",
          content: "Open Task",
          icon: "fas fa-external-link-alt",
        },
      ],

    };
  },
  methods: {
    /**
     * Show the sidebar
     */
    showSideBar(info, data, firstTime = false, size = null) {
      if (size) {
        this.splitpaneSize = size;
      }
      let param = "";
      this.stopFrame = false;
      this.taskTitle = info.element_name;
      this.showFrame1 = firstTime ? true : this.showFrame1;
      this.task = info;
      this.customFilter();
      if (this.showFrame === 1) {
        this.linkTasks1 = `/tasks/${info.id}/edit/preview`+param;
        this.showFrame1 = true;
      }
      if (this.showFrame === 2) {
        this.showFrame2 = true;
        this.linkTasks2 = `/tasks/${info.id}/edit/preview`+param;
      }
      this.showPreview = true;
      this.data = data;
      this.existPrev = false;
      this.existNext = false;
      this.defineNextPrevTask();
    },
    customFilter() {
      this.propFilters = {
        order: { by: "created_at", direction: "desc" },
        filters:[
        {
          subject: { type: "Field", value: "process_id" },
          operator: "=",
          value: this.task.process_id,
        },
        {
          subject: { type: "Field", value: "element_id" },
          operator: "=",
          value: this.task.element_id
        }],
      }
    },
    showButton() {
      this.isMouseOver = true;
    },
    hideButton() {
      this.isMouseOver = false;
    },
    onClose() {
      this.$emit('mark-selected-row', 0);
      this.showPreview = false;
      this.resetToDefault();
    },
    resetToDefault() {
      this.linkTasks1 = "";
      this.linkTasks2 = "";
      this.task = {};
      this.data = [];
      this.previewData = [];
      this.taskTitle = "";
      this.prevTask = {};
      this.nextTask = {};
      this.existPrev = false;
      this.existNext = false;
      this.loading = true;
      this.showFrame = 1;
      this.showFrame1 = false;
      this.showFrame2 = false;
      this.isLoading = "";
      this.stopFrame = false;
      this.size = 50;
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
      if (this.task.id) {
        const url = `/tasks/${this.task.id}/edit`;
        window.location.href = url;
      }
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
        this.$emit('mark-selected-row', this.nextTask.id);
        this.showSideBar(this.nextTask, this.data);
      }
      if (action === "Prev") {
        this.$emit('mark-selected-row', this.prevTask.id);
        this.showSideBar(this.prevTask, this.data);
      }
    },
    /**
     * Show the frame when this is loaded
     */
    frameLoaded(iframe) {
      this.isDisabled = false;
      this.$root.$emit('disable-button', this.isDisabled);
      const successMessage = this.$t('Task Filled successfully');
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
      if(this.useThisDataButton) {
        ProcessMaker.alert(successMessage, 'success');
        this.useThisDataButton = false;
      }

    },
    addPriority() {
      ProcessMaker.apiClient
        .put(`tasks/${this.task.id}/setPriority`, { is_priority: !this.isPriority })
        .then(() => {
          this.task.is_priority = !this.task.is_priority;
        });
    },
    onProcessNavigate(action, data) {
      switch (action.value) {
        case "clear-draft":
          ProcessMaker.apiClient
            .delete("drafts/" + this.task.id)
            .then(() => {
              this.isLoading = setTimeout(() => {
                this.stopFrame = true;
                this.taskTitle = this.$t("Task Lorem");
              }, 4900);
              this.showSideBar(this.task, this.data);
              this.task.draft = null;
            });
          break;
        case "quick-fill":
          this.showQuickFillPreview = true;
          this.size = 50;
          break;
        case "mark-priority":
            this.addPriority();
          break;
        case "open-task":
          this.openTask();
          break;
      }
    },
    updateScreenWidthPx() {
      this.screenWidthPx = window.innerWidth;
    },
    convertPercentageToPx(percentage) {
      return (this.screenWidthPx * percentage) / 100;
    },
    headerResponsive() {
      if (this.convertPercentageToPx(this.size) <= 550) {
        this.ellipsisButton = true;
        return this.convertPercentageToPx(this.size) - 400;
      }
      this.ellipsisButton = false;
      return this.convertPercentageToPx(this.size) - 550;
    },
  },
};

export default PreviewMixin;
