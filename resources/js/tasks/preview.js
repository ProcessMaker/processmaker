import { submitCollectionData } from "./utils/index";
// const store = new Vuex.Store();
const main = new Vue({
  // store: store,
  el: "#task",
  mixins: addons,
  data: {
    // Edit data
    fieldsToUpdate: [],
    jsonData: "",
    monacoLargeOptions: {
      automaticLayout: true,
    },
    showJSONEditor: false,
    windowParent: window.parent.ProcessMaker,
    // Reassignment
    selected: null,
    selectedIndex: -1,
    usersList: [],
    filter: "",
    showReassignment: false,
    task,
    userHasAccessToTask,
    statusCard: "card-header text-capitalize text-white bg-success",
    selectedUser: [],
    hasErrors: false,
    redirectInProcess: false,
    formData: {},
    submitting: false,
    userIsAdmin,
    userIsProcessManager,
    is_loading: false,
    autoSaveDelay: 5000,
    userHasInteracted: false,
    initialFormDataSet: false,
    alwaysAllowEditing: window.location.search.includes("alwaysAllowEditing=1"),
    disableInterstitial: window.location.search.includes("disableInterstitial=1"),
    validateForm: true,
  },
  computed: {
    screenFilteredData() {
      return this.filterScreenFields(this.formData);
    },
    taskDefinitionConfig() {
      const config = {};
      if (this.task.definition && this.task.definition.config) {
        return JSON.parse(this.task.definition.config);
      }
      return {};
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
      return this.selectedUser ? this.selectedUser.length === 0 : true;
    },
    styleDataMonaco() {
      const height = window.innerHeight * 0.55;
      return `height: ${height}px; border:1px solid gray;`;
    },
  },
  watch: {
    task: {
      deep: true,
      handler(task, oldTask) {
        window.ProcessMaker.breadcrumbs.taskTitle = task.element_name;
        if (task && oldTask && task.id !== oldTask.id) {
          history.replaceState(null, null, `/tasks/${task.id}/edit/preview`);
        }
      },
    },
    screenFilteredData: {
      deep: true,
      handler() {
        this.sendEvent("dataUpdated", this.screenFilteredData);
      },
    },
  },
  mounted() {
    this.prepareData();

    window.addEventListener("sendValidateForm", (event) => {
      this.validateForm = event.detail;
    });

    window.addEventListener("fillData", (event) => {
      const newData = {};
      screenFields.forEach((field) => {
        const existingValue = _.get(this.formData, field, null);

        let quickFillValue;

        if (existingValue) {
          // If the value exists in the task data, don't overwrite it
          quickFillValue = existingValue;
        } else {
          // use the value from the quick fill(event.detail)
          quickFillValue = _.get(event.detail, field, null);
        }

        if (this.validateBase64(quickFillValue)) {
          _.set(newData, field, existingValue);
          return;
        }
        // Set the value. This handles nested values using dot notation in 'field' string
        _.set(newData, field, quickFillValue);
      });

      this.formData = newData;
    });

    // Used by inbox rules new/edit interface. With inbox rules, we always
    // want to use all data saved in the inbox rule db record, regardless
    // if the field exists or not.
    window.addEventListener("fillDataOverwriteExistingFields", (event) => {
      for (const key in event.detail) {
        if (event.detail.hasOwnProperty(key)) {
          const value = event.detail[key];
          if (this.validateBase64(value)) {
            delete event.detail[key];
          }
        }
      }
      this.formData = _.merge(_.cloneDeep(this.formData), event.detail);
    });

    window.addEventListener("eraseData", (event) => {
      this.formData = {};
    });

    // listen for keydown on element with id interactionListener
    const interactionListener = document.getElementById("interactionListener");
    interactionListener.addEventListener("mousedown", (event) => {
      this.sendUserHasInteracted();
    });
    interactionListener.addEventListener("keydown", (event) => {
      this.sendUserHasInteracted();
    });
  },
  methods: {
    afterSubmit(event) {
      event.validation = this.validateForm;
    },
    filterScreenFields(taskData) {
      const filteredData = {};
      screenFields.forEach((field) => {
        _.set(filteredData, field, _.get(taskData, field, null));
      });
      return filteredData;
    },
    sendEvent(name, data) {
      const event = new CustomEvent(name, {
        detail: {
          event_parent_id: Number(window.frameElement.getAttribute("event-parent-id")),
          data,
        },
      });
      window.parent.dispatchEvent(event);
    },
    sendUserHasInteracted() {
      if (!this.userHasInteracted) {
        this.userHasInteracted = true;
        this.sendEvent("userHasInteracted", true);
      }
    },
    completed(processRequestId) {
      // avoid redirection if using a customized renderer
      if (this.task.component && this.task.component === "AdvancedScreenFrame") {
        return;
      }
      setTimeout(() => {
        parent.location.reload();
      }, 200);
    },
    error(processRequestId) {
      this.$refs.task.showSimpleErrorMessage();
    },
    redirectToTask(task, force = false) {
      this.redirect(`/tasks/${task}/edit/preview`, force);
    },
    closed(taskId) {
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
          this.windowParent.alert(this.$t("The task was successfully claimed"), "primary", 5, true);
          parent.location.reload();
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
          this.windowParent.alert(this.$t("The request data was saved."), "success");
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
      this.showReassignment = true;
    },
    cancelReassign() {
      this.showReassignment = false;
      this.selectedUser = [];
    },
    reassignUser() {
      if (this.selectedUser) {
        ProcessMaker.apiClient
          .put(`tasks/${this.task.id}`, {
            user_id: this.selectedUser.id,
          })
          .then((response) => {
            this.showReassignment = false;
            this.selectedUser = [];
            this.redirect("/tasks");
          });
      }
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
    submit(task, loading, buttonInfo) {
      if (window.location.search.includes("dispatchSubmit=1")) {
        this.sendEvent("formSubmit", buttonInfo);
      } else if (this.isSelfService) {
        this.windowParent.alert(this.$t("Claim the Task to continue."), "warning");
      } else {
        if (this.submitting) {
          return;
        }

        // Save collection data
        // This code is copied from tasks/edit.js, we should improve it
        submitCollectionData(task, this.formData);

        const message = this.$t("Task Completed Successfully");
        const taskId = task.id;
        this.submitting = true;
        ProcessMaker.apiClient
          .put(`tasks/${taskId}`, { status: "COMPLETED", data: this.formData })
          .then(() => {
            this.windowParent.alert(message, "success", 5, true);
          })
          .catch((error) => {
          // If there are errors, the user will be redirected to the request page
          // to view error details. This is done in loadTask in Task.vue
            if (error.response?.status && error.response?.status === 422) {
            // Validation error
              Object.entries(error.response.data.errors).forEach(([key, value]) => {
                this.windowParent.alert(`${key}: ${value[0]}`, "danger", 0);
              });
            }
          }).finally(() => {
            this.submitting = false;
            setTimeout(() => {
              parent.location.reload();
            }, 200);
          });
      }
    },
    taskUpdated(task) {
      this.task = task;
      this.formData = _.cloneDeep(this.$refs.task.requestData);
      this.$nextTick(() => {
        this.sendEvent("taskReady", this.task?.id);
      });
    },
    validateBase64(field) {
      const regex = /^data:image\/\w+;base64,/;
      return regex.test(field);
    },
  },
});
