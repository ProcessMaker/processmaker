const ProcessHeader = {
  data() {
    return {
      processId: null,
      processTemplateName: "",
      pmBlockName: "",
      assetName: "",
      processLaunchpadActions: [],
      optionsData: {},
      infoCollapsed: true,
      largeDescription: false,
      readActivated: false,
      showEllipsis: false,
      labelTooltip: "",
      showBookmarkIcon: false,
      auxBookmarkId: this.process.bookmark_id ?? 0,
    };
  },
  mounted() {
    this.getActions();
    this.checkShowEllipsis();
    this.optionsData = {
      id: this.process.id.toString(),
      type: "Process",
    };
    this.bookmarkIcon();
  },
  methods: {
    /**
     * Verify if the process is marked
     */
    bookmarkIcon() {
      this.labelTooltip = this.process.bookmark_id !== 0
        ? this.$t("Remove from My Bookmarks") : this.$t("Add to My Bookmarks");
      this.showBookmarkIcon = this.process.bookmark_id ? this.process.bookmark_id!== 0 : 0;
    },
    /**
     * Check the bookmark to add bookmarked list or remove it
     */
    checkBookmark(process) {
      if (this.auxBookmarkId) {
        ProcessMaker.apiClient
          .delete(`process_bookmarks/${this.auxBookmarkId}`)
          .then(() => {
            ProcessMaker.alert(this.$t("Process removed from Bookmarked List."), "success");
            this.labelTooltip = this.$t("Add to My Bookmarks");
            this.showBookmarkIcon = false;
            this.auxBookmarkId = 0;
          });
        return;
      }
      ProcessMaker.apiClient
        .post(`process_bookmarks/${process.id}`)
        .then(($response) => {
          ProcessMaker.alert(this.$t("Process added to Bookmarked List."), "success");
          this.labelTooltip = this.$t("Remove from My Bookmarks");
          this.showBookmarkIcon = true;
          this.auxBookmarkId = $response.data.newId;
        });
    },
    showCreateTemplateModal(name, id) {
      this.processId = id;
      this.processTemplateName = name;
      this.$refs["create-template-modal"].show();
    },
    showPmBlockModal(name, id) {
      this.processId = id;
      this.pmBlockName = name;
      this.$refs["create-pm-block-modal"].show();
    },
    showAddToProjectModal(name, id) {
      this.processId = id;
      this.assetName = name;
      this.assetType = "process";
      this.$refs["add-to-project-modal"].show();
    },
    showAddToModalSaveVersion(name, id) {
      this.processId = id;
      this.assetName = name;
      this.assetType = "process";
      this.$refs["launchpad-settings-modal"].showModal();
    },
    getActions() {
      this.processLaunchpadActions = this.processActions
        .filter((action) => action.value !== "open-launchpad");

      const newAction = {
        value: "archive-item-launchpad",
        content: "Archive",
        permission: ["archive-processes", "view-additional-asset-actions"],
        icon: "fas fa-archive",
        conditional: "if(status == 'ACTIVE' or status == 'INACTIVE', true, false)",
      };
      this.processLaunchpadActions = this.processLaunchpadActions.map((action) => (action.value !== "archive-item" ? action : newAction));
    },
    checkShowEllipsis() {
      const permissionsNeeded = [
        "archive-processes",
        "view-additional-asset-actions",
        "export-processes",
        "view-processes",
        "edit-processes",
        "create-projects",
        "create-pm-blocks",
        "create-process-templates",
        "view-projects",
      ];
      this.showEllipsis = this.permission.some( (permission) => permissionsNeeded.includes(permission));
    },
    /**
     * Return a process cards from process info
     */
    goBack() {
      this.$emit("goBackCategory");
    },
    getNameEllipsis() {
      const name = this.process.name;
      const nameEllipsis = name.length <= 70 ? name : name.slice(0, 70) + "...";
      return nameEllipsis;
    },
  },
};

export default ProcessHeader;
