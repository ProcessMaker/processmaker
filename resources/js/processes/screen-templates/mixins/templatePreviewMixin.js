const templatePreviewMixin = {
  data() {
    return {
      showPreview: false,
      showRight: true,
      template: {},
      data: [],
      templateTitle: "",
      loading: true,
      isLoading: "",
      stopFrame: false,
      formData: {},
      splitpaneSize: 50,
      size: 50,
      screenWidthPx: 0,
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
      this.stopFrame = false;
      this.templateTitle = info.name;
      this.template = info;
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
      this.data = [];
      this.previewData = [];
      this.templateTitle = "";
      this.loading = true;
      this.showFrame = 1;
      this.isLoading = "";
      this.stopFrame = false;
      this.size = 50;
    },
  },
};

export default templatePreviewMixin;
