import TaskSaveNotification from "./components/TaskSaveNotification.vue";
import TasksList from "./components/TasksList.vue";
import TaskSavePanel from "./components/TaskSavePanel.vue";
import autosaveMixins from "../modules/autosave/autosaveMixin";
import draftFileUploadMixin from "../modules/autosave/draftFileUploadMixin";
import reassignMixin from "../common/reassignMixin";
import translator from "../modules/lang.js";

Vue.mixin(autosaveMixins);
Vue.mixin(draftFileUploadMixin);
Vue.mixin(reassignMixin);
Vue.component("DataTreeToggle", () => import("../components/common/data-tree-toggle.vue"));
Vue.component("TreeView", () => import("../components/TreeView.vue"));
window.__ = translator;

const main = new Vue({
  el: "#task",
  mixins: addons,
  components: {
    TaskSaveNotification,
    TaskSavePanel,
    TasksList,
  },
  data: {
    // Edit data
    fieldsToUpdate: [],
    jsonData: "",
    monacoLargeOptions: {
      automaticLayout: true,
    },
    showJSONEditor: false,

    // Reassignment
    selected: null,
    selectedIndex: -1,
    usersList: [],
    filter: "",
    showReassignment: false,
    task,
    draftTask,
    userHasAccessToTask,
    hasErrors: false,
    redirectInProcess: false,
    formData: {},
    submitting: false,
    userIsAdmin,
    userIsProcessManager,
    showTree: false,
    is_loading: false,
    autoSaveDelay: 2000,
    options: {
      is_loading: false,
    },
    lastAutosave: "-",
    lastAutosaveNav: "-",
    errorAutosave: false,
    formDataWatcherActive: true,
    showInfo: true,
    isPriority: false,
    userHasInteracted: false,
    caseTitle: "",
    showMenu: true,
    userConfiguration,
    urlConfiguration: "users/configuration",
    showTabs: true,
  },
  computed: {
    taskDefinitionConfig() {
      const config = {};
      if (this.task.definition && this.task.definition.config) {
        return JSON.parse(this.task.definition.config);
      }
      return {};
    },
    taskHasComments() {
      return "comment-editor" in Vue.options.components;
    },
    dueLabel() {
      const dueLabels = {
        open: "Due",
        completed: "Completed",
        overdue: "Due",
      };
      return dueLabels[this.task.advanceStatus] || "";
    },
    isSelfService() {
      return this.task.process_request.status === "ACTIVE" && this.task.is_self_service;
    },
    dateDueAt() {
      return this.task.due_at;
    },
    createdAt() {
      return this.task.created_at;
    },
    completedAt() {
      return this.task.completed_at;
    },
    showDueAtDates() {
      return this.task.status !== "CLOSED";
    },
    disabled() {
      return !this.selectedUser;
    },
    styleDataMonaco() {
      const height = window.innerHeight * 0.55;
      return `height: ${height}px; border:1px solid gray;`;
    },
    panCommentInVueOptionsComponents() {
      return "pan-comment" in Vue.options.components;
    },
    statusCard() {
      const header = {
        OVERDUE: "overdue-style",
        OPEN: "open-style",
        COMPLETED: "open-style",
        TRIGGERED: "open-style",
      };
      const status = (this.task.advanceStatus || "").toUpperCase();
      return `card-header text-status ${header[status]}`;
    },
  },
  watch: {
    task: {
      deep: true,
      handler(task, oldTask) {
        window.ProcessMaker.breadcrumbs.taskTitle = task.element_name;
        if (task && oldTask && task.id !== oldTask.id) {
          history.replaceState(null, null, `/tasks/${task.id}/edit`);
          this.setAllowReassignment();
        }
        if (task.draft) {
          this.lastAutosave = moment(this.draftTask.updated_at).format("DD MMMM YYYY | HH:mm");
          this.lastAutosaveNav = moment(this.draftTask.updated_at).format("MMM DD, YYYY / HH:mm");
        } else {
          this.lastAutosave = "-";
          this.lastAutosaveNav = "-";
        }
        if (task.id !== oldTask.id) {
          this.editJsonData();
        }
      },
    },
    formData: {
      deep: true,
      handler(formData) {
        if (this.userHasInteracted) {
          if (this.formDataWatcherActive) {
            this.handleAutosave();
            this.userHasInteracted = false;
          } else {
            this.formDataWatcherActive = true;
          }
        }
      },
    },
  },
  mounted() {
    this.caseTitleField(this.task);
    this.prepareData();
    window.ProcessMaker.isSelfService = this.isSelfService;
    this.isPriority = task.is_priority;
    // listen for keydown on element with id interactionListener
    const interactionListener = document.getElementById("interactionListener");
    interactionListener.addEventListener("mousedown", (event) => {
      this.sendUserHasInteracted();
    });
    interactionListener.addEventListener("keydown", (event) => {
      this.sendUserHasInteracted();
    });
    this.defineUserConfiguration();
    this.setAllowReassignment();
  },
  methods: {
    defineUserConfiguration() {
      this.userConfiguration = JSON.parse(this.userConfiguration.ui_configuration);
      this.showMenu = this.userConfiguration.tasks.isMenuCollapse;
    },
    hideMenu() {
      this.showMenu = !this.showMenu;
      this.$root.$emit("sizeChanged", !this.showMenu);
      this.updateUserConfiguration();
    },
    updateUserConfiguration() {
      this.userConfiguration.tasks.isMenuCollapse = this.showMenu;
      ProcessMaker.apiClient
        .put(
          this.urlConfiguration,
          {
            ui_configuration: this.userConfiguration,
          },
        )
        .catch((error) => {
          console.error("Error", error);
        });
    },
    createRule() {
      const processId = this.task.process_id || this.task.process_request?.process_id;
      window.location.href = "/tasks/rules/new?"
      + `task_id=${this.task.id}&`
      + `element_id=${this.task.element_id}&`
      + `process_id=${processId}`;
    },
    completed(processRequestId, endEventDestination = null) {
      // avoid redirection if using a customized renderer
      if (this.task.component && this.task.component === "AdvancedScreenFrame") {

      }
    },
    error(processRequestId) {
      this.$refs.task.showSimpleErrorMessage();
    },
    redirectToTask(task, force = false) {
      this.redirect(`/tasks/${task}/edit`, force);
    },
    closed(taskId, elementDestination = null) {
      // avoid redirection if using a customized renderer
      if (this.task.component && this.task.component === "AdvancedScreenFrame") {
        return;
      }
      this.redirect("/tasks");
    },
    claimTask() {
      ProcessMaker.apiClient
        .put(`tasks/${this.task.id}`, {
          user_id: window.ProcessMaker.user.id,
          is_self_service: 0,
        })
        .then((response) => {
          // Save the current URL to redirect after the task is claimed
          sessionStorage.setItem("sessionUrlSelfService", document.referrer);

          window.location.reload();
        });
    },
    // Data editor
    updateRequestData() {
      const data = JSON.parse(this.jsonData);
      ProcessMaker.apiClient
        .put(`requests/${this.task.process_request_id}`, {
          data,
          task_element_id: this.task.element_id,
        })
        .then((response) => {
          this.fieldsToUpdate.splice(0);
          ProcessMaker.alert(this.$t("The request data was saved."), "success");
        });
    },
    saveJsonData() {
      try {
        const value = JSON.parse(this.jsonData);
        this.updateRequestData();
      } catch (e) {
        // Invalid data
      }
    },
    editJsonData() {
      this.jsonData = JSON.stringify(this.task.request_data, null, 4);
    },
    // Reassign methods
    show() {
      this.selectedUser = null;
      this.showReassignment = true;
      this.getReassignUsers();
    },
    showQuickFill() {
      this.redirect(`/tasks/${this.task.id}/edit/quickfill`);
    },
    cancelReassign() {
      this.selectedUser = null;
      this.showReassignment = false;
    },
    redirect(to, forceRedirect = false) {
      if (this.redirectInProcess && !forceRedirect) {
        return;
      }
      this.redirectInProcess = true;
      window.location.href = to;
    },
    assignedUserAvatar(user) {
      return [{
        src: user.avatar,
        name: user.fullname,
      }];
    },
    resizeMonaco() {
      this.showTree = false;
      const editor = this.$refs.monaco.getMonaco();
      editor.layout({ height: window.innerHeight * 0.65 });
    },
    prepareData() {
      this.updateRequestData = debounce(this.updateRequestData, 1000);
      this.editJsonData();
    },
    updateTask(val) {
      this.$set(this, "task", val);
    },
    processCollectionData(task) {
      const results = [];
      // Verify if object "screen" exists
      if (task.screen) {
        // Verify if "config" array exists and it has at least one element
        if (Array.isArray(task.screen.config) && task.screen.config.length > 0) {
          // Iteration on "config" array
          for (const configItem of task.screen.config) {
            // Verify if "items" array exists
            if (Array.isArray(configItem.items)) {
              // Iteration over each "items" element
              for (const item of configItem.items) {
                // Verify if component "FormCollectionRecordControl" is inside the screen
                if (item.component === "FormCollectionRecordControl") {
                  // Access to FormCollectionRecordControl "config" object
                  const { config } = item;

                  // Saving values into variables
                  const collectionFields = config.collection.data[0];
                  const submitCollectionChecked = config.collectionmode.submitCollectionCheck;
                  let recordId = "";
                  const { record } = config;

                  if (this.isMustache(record)) {
                    recordId = Mustache.render(record, this.formData);
                  } else {
                    recordId = parseInt(record, 10);
                  }
                  const { collectionId } = config.collection;
                  // Save the values into the results array
                  results.push({
                    submitCollectionChecked, recordId, collectionId, collectionFields,
                  });
                }
              }
            }
          }
        }
      }
      return results.length > 0 ? results : null;
    },
    isMustache(record) {
      return /\{\{.*\}\}/.test(record);
    },
    submit(task) {
      if (this.isSelfService) {
        ProcessMaker.alert(this.$t("Claim the Task to continue."), "warning");
      } else {
        if (this.submitting) {
          return;
        }

        // If screen has CollectionControl components saves collection data (if submit check is true)
        const resultCollectionComponent = this.processCollectionData(this.task);
        const messageCollection = this.$t("Collection data was updated");

        if (resultCollectionComponent && resultCollectionComponent.length > 0) {
          resultCollectionComponent.forEach((result) => {
            if (result.submitCollectionChecked) {
              const collectionKeys = Object.keys(result.collectionFields);
              const matchingKeys = _.intersection(Object.keys(this.formData), collectionKeys);
              const collectionsData = _.pick(this.formData, matchingKeys);

              ProcessMaker.apiClient
                .put(`collections/${result.collectionId}/records/${result.recordId}`, {
                  data: collectionsData,
                  uploads: [],
                })
                .then(() => {
                  window.ProcessMaker.alert(messageCollection, "success", 5, true);
                });
            }
          });
        }

        const message = this.$t("Task Completed Successfully");
        const taskId = task.id;
        this.submitting = true;
        ProcessMaker.apiClient
          .put(`tasks/${taskId}`, { status: "COMPLETED", data: this.formData })
          .then(() => {
            window.ProcessMaker.alert(message, "success", 5, true);
          })
          .catch((error) => {
          // If there are errors, the user will be redirected to the request page
          // to view error details. This is done in loadTask in Task.vue
            if (error.response?.status && error.response?.status === 422) {
            // Validation error
              if (error.response.data.errors) {
                Object.entries(error.response.data.errors).forEach(([key, value]) => {
                  window.ProcessMaker.alert(`${key}: ${value[0]}`, "danger", 0);
                });
              } else if (error.response.data.message) {
                window.ProcessMaker.alert(error.response.data.message, "danger", 0);
              }
              this.$refs.task.loadNextAssignedTask();
            }
          }).finally(() => {
            this.submitting = false;
          });
      }
    },
    taskUpdated(task) {
      this.task = task;
    },
    updatePage() {
      document.getElementById("tabContent").scrollTop = 0;
    },
    updateScreenFields(taskId) {
      return ProcessMaker.apiClient
        .get(`tasks/${taskId}/screen_fields`)
        .then((response) => {
          screenFields = response.data;
        });
    },
    autosaveApiCall() {
      if (!this.taskDraftsEnabled) {
        return;
      }
      this.options.is_loading = true;
      const draftData = {};

      const saveDraft = () => {
        screenFields.forEach((field) => {
          _.set(draftData, field, _.get(this.formData, field));
        });

        return ProcessMaker.apiClient
          .put(`drafts/${this.task.id}`, draftData)
          .then((response) => {
            this.task.draft = _.merge(
              {},
              this.task.draft,
              response.data,
            );
            this.draftTask = structuredClone(response.data);
          })
          .catch(() => {
            this.errorAutosave = true;
          })
          .finally(() => {
            this.options.is_loading = false;
          });
      };
      if (screenFields.length === 0) {
        return this.updateScreenFields(this.task.id)
          .then(() => saveDraft());
      }
      return saveDraft();
    },
    eraseDraft() {
      this.formDataWatcherActive = false;
      ProcessMaker.apiClient
        .delete(`drafts/${this.task.id}`)
        .then((response) => {
          this.resetRequestFiles(response);
          this.task.draft = null;
          const taskComponent = this.$refs.task;
          taskComponent.loadTask();
        });
    },
    addPriority() {
      ProcessMaker.apiClient
        .put(`tasks/${this.task.id}/setPriority`, { is_priority: !this.isPriority })
        .then((response) => {
          this.isPriority = !this.isPriority;
        });
    },
    sendUserHasInteracted() {
      if (!this.userHasInteracted) {
        this.userHasInteracted = true;
      }
    },
    handleFormDataChange() {
      if (this.userHasInteracted) {
        this.handleAutosave();
        this.userHasInteracted = false;
      }
    },
    switchTabInfo(tab) {
      this.showInfo = !this.showInfo;
    },
    collapseTabs() {

    },
    caseTitleField(task) {
      this.caseTitle = task.process_request.case_title;
    },
    getCommentsData: async () => {
      const response = await ProcessMaker.apiClient.get("comments-by-case", {
        params: {
          type: "COMMENT,REPLY",
          order_direction: "desc",
          case_number: task?.process_request?.case_number,
        },
      });

      return response;
    },
  },
});
