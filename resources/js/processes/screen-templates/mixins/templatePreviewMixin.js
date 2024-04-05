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
    previewTemplate(info, size = null) {
      this.selectedRow = info.id;
      this.selectedTemplate = info;
      this.$refs.preview.showSideBar(info, this.data.data, true, size);
    },
    handleRowMouseover(row) {
      this.clearHideTimer();

      const tableContainer = document.getElementById(this.tableId);
      const rectTableContainer = tableContainer.getBoundingClientRect();
      const topAdjust = rectTableContainer.top;

      let elementHeight = 36;

      this.isTooltipVisible = true;
      this.tooltipRowData = row;

      const rowElement = tableContainer.querySelector(`#row-${row.id}`);
      const rect = rowElement.getBoundingClientRect();

      const leftBorderX = rect.left;
      // The higher the value added to the topAdjust, the lower the tooltip appears
      const topBorderY = rect.top - topAdjust + 110 - elementHeight;

      this.rowPosition = {
        x: leftBorderX,
        y: topBorderY,
      };
    },
    hideTooltip() {
      this.isTooltipVisible = false;
    },
    clearHideTimer() {
      clearTimeout(this.hideTimer);
    },
    handleRowMouseleave(visible) {
      this.startHideTimer();
    },
    startHideTimer() {
      this.hideTimer = setTimeout(() => {
        this.hideTooltip();
      }, 700);
    },
    markSelectedRow(value) {
      this.selectedRow = value;
    },
    hidePreview() {
      this.showTemplatePreview = false;
      this.selectedTemplate = null;
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
