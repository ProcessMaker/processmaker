import Vue from "vue";
import CaseDetail from "./components/CaseDetail.vue";
import Tabs from "./components/Tabs.vue";
import Timeline from "../../../js/components/Timeline.vue";
import { CollapsableContainer } from "../../base";

const caseDetail = new Vue({
  el: "#case-detail",
  components: {
    CaseDetail, Tabs, CollapsableContainer, Timeline,
  },
  data() {
    return {
      data,
      request,
      canCancel,
      canViewPrint,
      status: "ACTIVE",
      userRequested: [],
      errorLogs,
      packages: [],
      processId,
      canViewComments,
      tabDefault: "details",
      tabs: [
        {
          name: "Details",
          href: "#details",
          current: "details",
          show: true,
          content: null,
        },
        {
          name: "Comments",
          href: "#comments",
          current: "comments",
          show: true,
          content: null,
        },
      ],
      headerModel: false,
    };
  },
  computed: {
    /**
     * Get the list of participants in the request.
     *
     */
    participants() {
      return this.request.participants;
    },
    classStatusCard() {
      const header = {
        ACTIVE: "active-style",
        COMPLETED: "active-style",
        CANCELED: "canceled-style ",
        ERROR: "canceled-style",
      };
      return `tw-rounded-md text-status ${header[this.request.status.toUpperCase()]}`;
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
  },
  methods: {
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
  },
});
