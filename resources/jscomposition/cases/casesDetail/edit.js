import Vue from "vue";
import CaseDetail from "./components/CaseDetail.vue";
import Tabs from "./components/Tabs.vue";
import Timeline from "../../../js/components/Timeline.vue";
import { CollapsableContainer } from "../../base";
import { cases } from "./store";
import { updateUserConfiguration, getUserConfiguration, getCommentsData } from "./api";
import { useStore, getRequest, getComentableType } from "./variables";

Vue.globalStore.registerModule("core:cases", cases);

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
      collapseContainer: true,
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
  async mounted() {
    const store = useStore();
    this.packages = window.ProcessMaker.requestShowPackages;

    const response = await this.getUserConf();

    store.commit("core:cases/updateUserConfiguration", response);
    this.collapseContainer = store.getters["core:cases/getCollapseContainer"];
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
    async onToogleContainer(value) {
      const store = useStore();
      store.commit("core:cases/updateCollapseContainer", value);

      const userConf = store.getters["core:cases/getUserConfiguration"];

      const response = await updateUserConfiguration({
        user_id: userConf.user_id,
        ui_configuration: userConf.ui_configuration,
      });
    },
    getUserConf: async () => {
      const response = await getUserConfiguration();

      return {
        user_id: response.user_id,
        ui_configuration: JSON.parse(response.ui_configuration),
      };
    },
    getCommentsData: async () => {
      const request = getRequest();

      const response = await getCommentsData({
        params: {
          type: "COMMENT,REPLY",
          case_number: request.case_number,
        },
      });

      return response;
    },
  },
});
