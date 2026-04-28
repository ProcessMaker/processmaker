const templatePreviewMixin = {
  data() {
    return {
      showPreview: false,
      showRight: true,
      template: {},
      prevTemplate: {},
      nextTemplate: {},
      existPrev: false,
      existNext: false,
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
      this.existPrev = false;
      this.existNext = false;
      this.defineNextPrevTemplate();
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
      this.tooltipRowData = row;
      this.clearHideTimer();

      const tableContainer = document.getElementById(this.tableId);
      const rectTableContainer = tableContainer.getBoundingClientRect();
      const topAdjust = rectTableContainer.top;

      let elementHeight = 36;

      this.isTooltipVisible = true;

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
    closePreviewFrame() {
      if (this.$refs?.preview?.onClose) {
        this.$refs.preview.onClose();
        return;
      }
      if (typeof this.onClose === "function") {
        this.onClose();
      }
    },
    bindPreviewTabClose(tabSelector) {
      if (!tabSelector || typeof $ === "undefined") {
        return;
      }
      this._previewTabSelector = tabSelector;
      this._previewTabHandler = () => {
        this.closePreviewFrame();
      };
      $(tabSelector).on("hide.bs.tab.templatePreview", this._previewTabHandler);
    },
    unbindPreviewTabClose() {
      if (!this._previewTabSelector || typeof $ === "undefined") {
        return;
      }
      $(this._previewTabSelector).off("hide.bs.tab.templatePreview", this._previewTabHandler);
      this._previewTabSelector = null;
      this._previewTabHandler = null;
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
      this.prevTemplate = {};
      this.nextTemplate = {};
      this.existPrev = false;
      this.existNext = false;
    },
    defineNextPrevTemplate() {
      if (!Array.isArray(this.data)) {
        this.prevTemplate = {};
        this.nextTemplate = {};
        this.existPrev = false;
        this.existNext = false;
        return;
      }

      let prevTemplate = {};
      let nextTemplate = {};
      let seeNextTemplate = false;
      for (const templateIndex in this.data) {
        if (!seeNextTemplate) {
          if (this.data[templateIndex] === this.template) {
            seeNextTemplate = true;
          } else {
            prevTemplate = this.data[templateIndex];
            this.existPrev = true;
          }
        } else {
          nextTemplate = this.data[templateIndex];
          this.existNext = true;
          break;
        }
      }
      this.prevTemplate = prevTemplate;
      this.nextTemplate = nextTemplate;
    },
    goPrevNext(action) {
      let targetTemplate = null;
      if (action === "Next" && this.existNext) {
        targetTemplate = this.nextTemplate;
      }
      if (action === "Prev" && this.existPrev) {
        targetTemplate = this.prevTemplate;
      }

      if (!targetTemplate) {
        return;
      }

      this.$emit("select-template", targetTemplate);
      this.$emit("mark-selected-row", targetTemplate.id);
      this.showSideBar(targetTemplate, this.data);
    },
  },
};

export default templatePreviewMixin;
