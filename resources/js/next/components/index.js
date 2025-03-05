import { getGlobalVariable } from "../globalVariables";

export default () => {
  const Vue = getGlobalVariable("Vue");

  const pmComponents = {
    // Components folder
    AvatarImage: () => import("../../components/AvatarImage.vue"),
    Breadcrumbs: () => import("../../components/Breadcrumbs.vue"),
    Confirm: () => import("../../components/Confirm.vue"),
    CustomActions: () => import("../../components/CustomActions.vue"),
    DetailRow: () => import("../../components/DetailRow.vue"),
    FilterBar: () => import("../../components/FilterBar.vue"),
    Menu: () => import("../../components/Menu.vue"),
    Message: () => import("../../components/Message.vue"),
    NavbarProfile: () => import("../../components/NavbarProfile.vue"),
    PMBadgesFilters: () => import("../../components/PMBadgesFilters.vue"),
    PMDatetimePicker: () => import("../../components/PMDatetimePicker.vue"),
    PMDropdownSuggest: () => import("../../components/PMDropdownSuggest.vue"),
    PMFloatingButtons: () => import("../../components/PMFloatingButtons.vue"),
    PMFormSelectSuggest: () => import("../../components/PMFormSelectSuggest.vue"),
    PMMessageResults: () => import("../../components/PMMessageResults.vue"),
    PMMessageScreen: () => import("../../components/PMMessageScreen.vue"),
    PMPanelWithCustomHeader: () => import("../../components/PMPanelWithCustomHeader.vue"),
    PMPopoverConfirmation: () => import("../../components/PMPopoverConfirmation.vue"),
    PMSearchBar: () => import("../../components/PMSearchBar.vue"),
    PMTable: () => import("../../components/PMTable.vue"),
    PMTabs: () => import("../../components/PMTabs.vue"),
    Recommendations: () => import("../../components/Recommendations.vue"),
    SelectFromApi: () => import("../../components/SelectFromApi.vue"),
    SelectLanguage: () => import("../../components/SelectLanguage.vue"),
    SelectScreen: () => import("../../components/SelectScreen.vue"),
    SelectStatus: () => import("../../components/SelectStatus.vue"),
    SelectUser: () => import("../../components/SelectUser.vue"),
    SelectUserGroup: () => import("../../components/SelectUserGroup.vue"),
    Session: () => import("../../components/Session.vue"),
    Sidebaricon: () => import("../../components/Sidebaricon.vue"),
    Timeline: () => import("../../components/Timeline.vue"),
    TimelineItem: () => import("../../components/TimelineItem.vue"),
    TreeView: () => import("../../components/TreeView.vue"),
    // Shared components folder
    AddToBundle: () => import("../../components/shared/AddToBundle"),
    AddToProjectModal: () => import("../../components/shared/AddToProjectModal"),
    AssetDependentTreeModal: () => import("../../components/shared/AssetDependentTreeModal.vue"),
    AssetTreeModal: () => import("../../components/shared/AssetTreeModal.vue"),
    BackendSelect: () => import("../../components/shared/BackendSelect.vue"),
    BasicSearch: () => import("../../components/shared/BasicSearch.vue"),
    CategorySelect: () => import("../../components/shared/CategorySelect.vue"),
    ChangeLog: () => import("../../components/shared/ChangeLog.vue"),
    ColorSchemeSelector: () => import("../../components/shared/ColorSchemeSelector.vue"),
    Column: () => import("../../components/shared/Column.vue"),
    ColumnChooser: () => import("../../components/shared/ColumnChooser.vue"),
    ColumnConfig: () => import("../../components/shared/ColumnConfig.vue"),
    DataCard: () => import("../../components/shared/DataCard.vue"),
    DataFormatSelector: () => import("../../components/shared/DataFormatSelector.vue"),
    DataMaskSelector: () => import("../../components/shared/DataMaskSelector.vue"),
    DataNode: () => import("../../components/shared/DataNode.vue"),
    DataTree: () => import("../../components/shared/DataTree.vue"),
    DownloadSvgButton: () => import("../../components/shared/DownloadSvgButton.vue"),
    DraggableFileUpload: () => import("../../components/shared/DraggableFileUpload.vue"),
    EllipsisMenu: () => import("../../components/shared/EllipsisMenu.vue"),
    FileUploadButton: () => import("../../components/shared/FileUploadButton.vue"),
    FilterTable: () => import("../../components/shared/FilterTable.vue"),
    IconDropdown: () => import("../../components/shared/IconDropdown.vue"),
    IconSelector: () => import("../../components/shared/IconSelector.vue"),
    InputImageCarousel: () => import("../../components/shared/InputImageCarousel.vue"),
    LaunchpadSettingsModal: () => import("../../components/shared/LaunchpadSettingsModal.vue"),
    Modal: () => import("../../components/shared/Modal.vue"),
    PmModal: () => import("../../components/shared/Modal.vue"),
    ModalSaveVersion: () => import("../../components/shared/ModalSaveVersion.vue"),
    MultiThumbnailFileUploader: () => import("../../components/shared/MultiThumbnailFileUploader.vue"),
    PaginationTable: () => import("../../components/shared/PaginationTable.vue"),
    PmqlInput: () => import("../../components/shared/PmqlInput.vue"),
    PmqlInputFilters: () => import("../../components/shared/PmqlInputFilters.vue"),
    ProjectSelect: () => import("../../components/shared/ProjectSelect.vue"),
    PTab: () => import("../../components/shared/PTab.vue"),
    PTabs: () => import("../../components/shared/PTabs.vue"),
    Required: () => import("../../components/shared/Required.vue"),
    SidebarButton: () => import("../../components/shared/SidebarButton.vue"),
    SidebarNav: () => import("../../components/shared/SidebarNav.vue"),
    SliderWithInput: () => import("../../components/shared/SliderWithInput.vue"),
    // Common components folder
    DataTreeToggle: () => import("../../components/common/data-tree-toggle.vue"),
    // Tasks components folder
    MobileTasks: () => import("../../tasks/components/MobileTasks.vue"),
    NavbarTaskMobile: () => import("../../tasks/components/NavbarTaskMobile.vue"),
    QuickFillPreview: () => import("../../tasks/components/QuickFillPreview.vue"),
    ReassignMobileModal: () => import("../../tasks/components/ReassignMobileModal.vue"),
    SplitpaneContainer: () => import("../../tasks/components/SplitpaneContainer.vue"),
    TaskDetailsMobile: () => import("../../tasks/components/TaskDetailsMobile.vue"),
    TaskListRowButtons: () => import("../../tasks/components/TaskListRowButtons.vue"),
    TaskLoading: () => import("../../tasks/components/TaskLoading.vue"),
    TaskSaveNotification: () => import("../../tasks/components/TaskSaveNotification.vue"),
    TaskSavePanel: () => import("../../tasks/components/TaskSavePanel.vue"),
    TasksHome: () => import("../../tasks/components/TasksHome.vue"),
    TasksList: () => import("../../tasks/components/TasksList.vue"),
    TasksListCounter: () => import("../../tasks/components/TasksListCounter.vue"),
    TasksPreview: () => import("../../tasks/components/TasksPreview.vue"),
    TaskTooltip: () => import("../../tasks/components/TaskTooltip.vue"),
    TaskView: () => import("../../tasks/components/TaskView.vue"),
    // Modeler components folder
    ModelerAssetQuickCreate: () => import("../../processes/modeler/components/inspector/ModelerAssetQuickCreate.vue"), // Decision engine
  };

  Object.entries(pmComponents).forEach(([key, component]) => {
    Vue.component(key, component);
  });

  // Multiselect
  Vue.component("Multiselect", (resolve, reject) => {
    import("@processmaker/vue-multiselect").then((Multiselect) => {
      resolve(Multiselect.Multiselect);
    }).catch(reject);
  });
};