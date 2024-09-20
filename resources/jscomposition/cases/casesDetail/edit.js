import Vue from "vue";
import CaseDetail from "./components/CaseDetail.vue";
import Timeline from "../../../js/components/Timeline.vue";

Vue.component("Timeline", Timeline);

new Vue({
  el: "#case-detail",
  components: { CaseDetail },
  data() {
    return {
      activeTab: "pending",
      showCancelRequest: false,
      fieldsToUpdate: [],
      jsonData: "",
      selectedData: "",
      monacoLargeOptions: {
        automaticLayout: true,
      },
      showJSONEditor: false,
      data: data,
      requestId: requestId,
      request: request,
      files: files,
      refreshTasks: 0,
      canCancel: canCancel,
      canViewPrint: canViewPrint,
      status: "ACTIVE",
      userRequested: [],
      errorLogs: errorLogs,
      disabled: false,
      retryDisabled: false,
      packages: [],
      processId: processId,
      canViewComments: canViewComments,
      isObjectLoading: false,
      showTree: false,
      showTabs: true,
      showInfo: true,
    };
  },
  computed: {
    activeErrors() {
      return this.request.status === "ERROR";
    },
    activePending() {
      return this.request.status === "ACTIVE";
    },
    /**
     * Get the list of participants in the request.
     *
     */
    participants() {
      return this.request.participants;
    },
    /**
     * Request Summary - that is blank place holder if there are in progress tasks,
     * if the request is completed it will show key value pairs.
     *
     */
    showSummary() {
      return this.request.status === "ACTIVE" || this.request.status === "COMPLETED" || this.request.status === "CANCELED";
    },
    /**
     * Show tasks if request status is not completed or pending
     *
     */
    showTasks() {
      return this.request.status !== "COMPLETED" && this.request.status !== "PENDING";
    },
    /**
     * If the screen summary is configured.
     */
    showScreenSummary() {
      return this.request.summary_screen !== null;
    },
    /**
     * Get the summary of the Request.
     *
     */
    summary() {
      return this.request.summary;
    },
    /**
     * Get Screen summary
     * */
    screenSummary() {
      return this.request.summary_screen;
    },
    /**
     * prepare data screen
     */
    dataSummary() {
      const options = {};
      this.request.summary.forEach((option) => {
        if (option.type === "datetime") {
          options[option.key] = moment(option.value)
            .tz(window.ProcessMaker.user.timezone)
            .format("MM/DD/YYYY HH:mm");
        } else if (option.type === "date") {
          options[option.key] = moment(option.value)
            .tz(window.ProcessMaker.user.timezone)
            .format("MM/DD/YYYY");
        } else {
          options[option.key] = option.value;
        }
      });
      return options;
    },
    /**
     * If the screen request detail is configured.
     */
    showScreenRequestDetail() {
      return !!this.request.request_detail_screen;
    },
    /**
     * Get Screen request detail
     */
    screenRequestDetail() {
      return this.request.request_detail_screen ? this.request.request_detail_screen.config : null;
    },
    classStatusCard() {
      const header = {
        ACTIVE: "active-style",
        COMPLETED: "active-style",
        CANCELED: "canceled-style ",
        ERROR: "canceled-style",
      };
      return `card-header text-status ${header[this.request.status.toUpperCase()]}`;
    },
    labelDate() {
      const label = {
        ACTIVE: "In Progress Since",
        COMPLETED: "Completed On",
        CANCELED: "Canceled ",
        ERROR: "Failed On",
      };
      return label[this.request.status.toUpperCase()];
    },
    statusDate() {
      const status = {
        ACTIVE: this.request.created_at,
        COMPLETED: this.request.completed_at,
        CANCELED: this.request.updated_at,
        ERROR: this.request.updated_at,
      };

      return status[this.request.status.toUpperCase()];
    },
    statusLabel() {
      const status = {
        ACTIVE: this.$t("In Progress"),
        COMPLETED: this.$t("Completed"),
        CANCELED: this.$t("Canceled"),
        ERROR: this.$t("Error"),
      };

      return status[this.request.status.toUpperCase()];
    },
    requestBy() {
      return [this.request.user];
    },
    panCommentInVueOptionsComponents() {
      return "pan-comment" in Vue.options.components;
    },
  },
  mounted() {
    this.packages = window.ProcessMaker.requestShowPackages;
    this.listenRequestUpdates();
    this.cleanScreenButtons();
    this.editJsonData();
  },
  methods: {
    switchTab(tab) {
      this.activeTab = tab;
      if (tab === "overview") {
        this.isObjectLoading = true;
      }
      ProcessMaker.EventBus.$emit("tab-switched", tab);
    },
    switchTabInfo(tab) {
      this.showInfo = !this.showInfo;
      if (window.Intercom) {
        window.Intercom("update", { hide_default_launcher: tab === "comments" });
      }
    },
    onLoadedObject() {
      this.isObjectLoading = false;
    },
    requestStatusClass(status) {
      const bubbleColor = {
        active: "text-success",
        inactive: "text-danger",
        error: "text-danger",
        draft: "text-warning",
        archived: "text-info",
        completed: "text-primary",
      };
      return `fas fa-circle ${bubbleColor[status.toLowerCase()]} small`;
    },
    // Data editor
    updateRequestData() {
      const data = JSON.parse(this.jsonData);
      ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
        data: data,
      }).then(() => {
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
      this.jsonData = JSON.stringify(this.data, null, 4);
    },
    /**
     * Refresh the Request details.
     *
     */
    refreshRequest() {
      this.$refs.pending.fetch();
      this.$refs.completed.fetch();
      ProcessMaker.apiClient.get(`requests/${this.requestId}`, {
        params: {
          include: "participants,user,summary,summaryScreen",
        },
      }).then((response) => {
        for (const attribute in response.data) {
          this.updateModel(this.request, attribute, response.data[attribute]);
        }
        this.refreshTasks++;
      });
    },
    /**
     * Update a model property.
     *
     */
    updateModel(obj, prop, value, defaultValue) {
      const descriptor = Object.getOwnPropertyDescriptor(obj, prop);
      value = value !== undefined ? value : (descriptor ? obj[prop] : defaultValue);
      if (descriptor && !(descriptor.get instanceof Function)) {
        delete obj[prop];
        Vue.set(obj, prop, value);
      } else if (descriptor && obj[prop] !== value) {
        Vue.set(obj, prop, value);
      }
    },
    /**
     * Listen for Request updates.
     *
     */
    listenRequestUpdates() {
      const userId = document.head.querySelector("meta[name=\"user-id\"]").content;
      Echo.private(`ProcessMaker.Models.User.${userId}`).notification((token) => {
        if (token.request_id === this.requestId) {
          this.refreshRequest();
        }
      });
    },
    /**
     * disable buttons in screen
     */
    cleanScreenButtons() {
      if (this.showScreenSummary) {
        this.$refs.screen.config[0].items.forEach((item) => {
          item.config.disabled = true;
          if (item.component === "FormButton") {
            item.config.event = "";
            item.config.variant = `${item.config.variant}  disabled`;
          }
        });
      }
    },
    okCancel() {
      //single click
      if (this.disabled) {
        return;
      }
      this.disabled = true;
      ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
        status: "CANCELED",
      }).then(() => {
        ProcessMaker.alert(this.$t("The request was canceled."), "success");
        window.location.reload();
      }).catch(() => {
        this.disabled = false;
      });
    },
    onCancel() {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want cancel this request?"),
        "",
        () => {
          this.okCancel();
        },
      );
    },
    completeRequest() {
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t("Are you sure you want to complete this request?"),
        "",
        () => {
          ProcessMaker.apiClient.put(`requests/${this.requestId}`, {
            status: "COMPLETED",
          }).then(() => {
            ProcessMaker.alert(this.$t("Request Completed"), "success");
            window.location.reload();
          });
        });
    },
    retryRequest() {
      const apiRequest = () => {
        this.retryDisabled = true;
        let success = true;

        ProcessMaker.apiClient.put(`requests/${this.requestId}/retry`).then((response) => {
          if (response.status !== 200) {
            return;
          }

          const { message } = response.data;
          success = response.data.success || false;

          if (success) {
            if (Array.isArray(message)) {
              for (const line of message) {
                ProcessMaker.alert(this.$t(line), "success");
              }
            }
          } else {
            ProcessMaker.alert(this.$t("Request could not be retried"), "danger");
          }
        }).finally(() => setTimeout(() => window.location.reload(), success ? 3000 : 1000));
      };

      ProcessMaker.confirmModal(
        this.$t("Confirm"),
        this.$t("Are you sure you want to retry this request?"),
        "default",
        apiRequest,
      );
    },
    rollback(errorTaskId, rollbackToName) {
      ProcessMaker.confirmModal(
        this.$t("Confirm"),
        this.$t("Are you sure you want to rollback to the task @{{name}}? Warning! This request will continue as the current published process version.",
          { name: rollbackToName },
        ),
        "default",
        () => {
          ProcessMaker.apiClient.post(`tasks/${errorTaskId}/rollback`).then(response => {
            window.location.reload();
          });
        },
      );
    },
  },
});
