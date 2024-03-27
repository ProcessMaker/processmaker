const templatePreviewMixin = {
    data() {
      return {
        showPreview: false,
        showRight: true,
        task: {},
        data: [],
        taskTitle: "",
        loading: true,
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
        isPriority: false,
        size: 50,
        screenWidthPx: 0,
      };
    },
    methods: {
      /**
       * Show the sidebar
       */
      showSideBar(info, data, firstTime = false, size = null) {
        console.log('HIT SHOW SIDEBAR');
        console.log('info', info);
        console.log('data', data);
        console.log('firstTime', firstTime);
        console.log('size', size);
        if (size) {
          this.splitpaneSize = size;
        }
        let param = "";
        this.stopFrame = false;
        this.taskTitle = info.element_name;
        this.showFrame1 = firstTime ? true : this.showFrame1;
        this.task = info;
        this.showPreview = true;
        this.data = data;
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
        this.task = {};
        this.data = [];
        this.previewData = [];
        this.taskTitle = "";
        this.loading = true;
        this.showFrame = 1;
        this.showFrame1 = false;
        this.showFrame2 = false;
        this.isLoading = "";
        this.stopFrame = false;
        this.size = 50;
      },
    },
  };
  
  export default templatePreviewMixin;
  