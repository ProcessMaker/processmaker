<template>
  <div
    v-if="!isArchived"
    id="processCollapseInfo"
    class="tw-relative"
  >
    <div id="processData">
      <div>
        <div class="header-mobile">
          <div class="title">
            {{ process.name }}
          </div>
        </div>
        <div class="header card card-body card-custom">
          <div class="d-flex justify-content-between">
            <div class="d-flex align-items-center flex-grow-1">
              <i
                class="fas fa-chevron-left mr-2 custom-color hover:tw-cursor-pointer"
                @click="goBack()"
              />
              <div
                v-b-tooltip.hover
                class="title text-truncate"
                :title="process.name"
              >
                {{ process.name }}
              </div>
            </div>
            <div class="d-flex align-items-center flex-shrink-0">
              <button
                class="info-button mx-3"
                :class="showProcessInfo ? 'info-button-active' : 'info-button'"
                @click="toggleInfo"
              >
                <span>i</span>
              </button>
              <div class="card-bookmark mx-3">
                <bookmark :process="process" />
              </div>
              <span class="ellipsis-border">
                <ellipsis-menu
                  v-if="showEllipsis"
                  :actions="processLaunchpadActions"
                  :data="process"
                  :divider="false"
                  :lauchpad="true"
                  variant="none"
                  :is-documenter-installed="$root.isDocumenterInstalled"
                  :permission="$root.permission || ellipsisPermission"
                  @navigate="ellipsisNavigate"
                />
              </span>
              <buttons-start
                :process="process"
                :start-event="singleStartEvent"
                :process-events="processEvents"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
    <create-template-modal
      id="create-template-modal"
      ref="create-template-modal"
      asset-type="process"
      :current-user-id="currentUserId"
      :asset-name="processTemplateName"
      :asset-id="processId"
    />
    <create-pm-block-modal
      id="create-pm-block-modal"
      ref="create-pm-block-modal"
      :current-user-id="currentUserId"
      :asset-name="pmBlockName"
      :asset-id="processId"
    />
    <add-to-project-modal
      id="add-to-project-modal"
      ref="add-to-project-modal"
      asset-type="process"
      :asset-id="processId"
      :asset-name="assetName"
    />
    <launchpad-settings-modal
      id="launchpad-settings-modal"
      ref="launchpad-settings-modal"
      asset-type="process"
      origin="core"
      :options="optionsData"
      :description-settings="process.description"
      :process="process"
      :my-tasks-columns="myTasksColumns"
      :my-cases-columns="myCasesColumns"
      @updateMyTasksColumns="updateMyTasksColumns"
    />
  </div>
</template>

<script>
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import LaunchpadSettingsModal from "../../components/shared/LaunchpadSettingsModal.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import ProcessesMixin from "./mixins/ProcessesMixin";
import ButtonsStart from "./optionsMenu/ButtonsStart.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import Bookmark from "./Bookmark.vue";

export default {
  components: {
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    LaunchpadSettingsModal,
    ButtonsStart,
    EllipsisMenu,
    Bookmark,
  },
  mixins: [ProcessesMixin, ellipsisMenuMixin, processNavigationMixin],
  props: ["process", "currentUserId", "ellipsisPermission", "myTasksColumns", "myCasesColumns"],
  data() {
    return {
      mobileApp: window.ProcessMaker.mobileApp,
      showProcessInfo: false,
      collapsed: true,
      infoCollapsed: true,
      processEvents: [],
      singleStartEvent: null,
    };
  },
  computed: {
    createdFromWizardTemplate() {
      return !!this.process?.properties?.wizardTemplateUuid;
    },
    isArchived() {
      return this.process?.status === "ARCHIVED";
    },
    wizardTemplateUuid() {
      return this.process?.properties?.wizardTemplateUuid;
    },
  },
  mounted() {
    this.verifyDescription();
    ProcessMaker.EventBus.$on("reloadByNewScreen", () => {
      window.location.reload();
    });
    this.getStartEvents();
  },
  methods: {
    /**
     * Verify if the Description is large
     */
    verifyDescription() {
      if (this.process.description.length > 200) {
        this.largeDescription = true;
      }
    },
    /**
     * Show the whole large description
     */
    activateReadMore() {
      this.readActivated = true;
    },
    getHelperProcess() {
      this.$refs.wizardHelperProcessModal.getHelperProcessStartEvent();
    },
    toggleInfo() {
      this.showProcessInfo = !this.showProcessInfo;
      this.$emit("toggle-info");
    },
    updateMyTasksColumns(columns) {
      this.$emit("updateMyTasksColumns", columns);
    },
    onProcessInfoCollapsed(collapsed) {
      this.collapsed = collapsed;
    },
    ellipsisNavigate(action, data) {
      this.onProcessNavigate(action, data);
    },
    /**
     * get start events for dropdown Menu
     */
    getStartEvents() {
      this.processEvents = [];
      ProcessMaker.apiClient
        .get(`process_bookmarks/processes/${this.process.id}/start_events`)
        .then((response) => {
          this.processEvents = response.data.data;
          if (this.processEvents.length === 0) {
            ProcessMaker.alert(this.$t("The current user does not have permission to start this process"), "danger");
          }
          const nonWebEntryStartEvents = this.processEvents.filter(
            (e) => !("webEntry" in e) || !e.webEntry,
          );
          if (nonWebEntryStartEvents.length === 1 && this.processEvents.length === 1) {
            this.singleStartEvent = nonWebEntryStartEvents[0].id;
          }
        })
        .catch((err) => {
          ProcessMaker.alert(err, "danger");
        });
    },
  },
};
</script>

<style lang="scss" scoped>
@import url("./scss/processes.css");
@import '~styles/variables';

#collapseProcessInfo {
  @media (max-width: $lp-breakpoint) {
    display: none;
  }
}
.custom-class {
 margin-top: -13px;
}
.wizard-link {
  text-transform: none;
}
.wizard-container {
  display: flex;
  flex-direction: column;
  align-items: center;
}
.wizard {
  display: flex;
  justify-items: end;
  width: 294px;
}
@media (width < 1200px) {
  .process-options {
    margin-top: 32px;
  }
  .wizard {
    width: 170px;
  }
}
@media (1460px <= width < 1600px) {
  .col-pm-9 {
    flex: 0 0 70%;
    max-width: 70%;
  }
  .col-pm-3 {
    flex: 0 0 30%;
    max-width: 30%;
  }
}
@media (1367px <= width < 1460px) {
  .col-pm-9 {
    flex: 0 0 70%;
    max-width: 70%;
  }
  .col-pm-3 {
    flex: 0 0 30%;
    max-width: 30%;
  }
}
@media (1200 <= width <= 1366) {
  .process-options {
    margin-top: 32px;
  }
  .col-pm-9 {
    flex: 0 0 100%;
    max-width: 100%;
  }
  .col-pm-3 {
    flex: 0 0 100%;
    max-width: 100%;
  }
}

.header {
  @media (max-width: $lp-breakpoint) {
    display: none;
  }
}

.header-mobile {
  display: none;
  padding: 1em;

  .title {
    flex: 1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 1.5em;
  }

  @media (max-width: $lp-breakpoint) {
    display: flex;
    flex-direction: row;
    align-items: center;
  }
}

/* Estilos integrados de ProcessHeaderStart */
.header-mobile .title,
.header .title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
}

.flex-grow-1 {
  flex-grow: 1;
}

.flex-shrink-0 {
  flex-shrink: 0;
}

.d-flex.align-items-center {
  min-width: 0;
}

.text-truncate {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  min-width: 0;
}

.card-bookmark {
  float: right;
  width: 20px;
  height: 23px;
}

.card-bookmark:hover {
  cursor: pointer;
}

.card-custom {
  background-color: #F6F9FB;
  border: 1px solid rgba(205, 221, 238, 0.125);
}

.title {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-align: left;
  max-width: 100%;
  font-size: 22px;
  letter-spacing: -0.2;
  color: #4C545C;
  font-weight: 400;
}

.custom-color {
  color: #4C545C;
}

.info-button {
  width: 20px;
  height: 20px;
  background-color: #6A7887;
  border: none;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-weight: 700;
  position: relative;

  span {
    color: #ffffff;
    font-size: 14px;
  }
}

.info-button-active {
  background-color: #2773F3 !important;

  &::before {
    content: '';
    position: absolute;
    top: -8px;
    left: -8px;
    right: -8px;
    bottom: -8px;
    background-color: rgba(106, 120, 135, 0.1);
    border-radius: 8px;
    z-index: 0;
    border: 1px solid #d3dbe2;
  }
}

.ellipsis-border div button span {
  font-size: 16px;
}
</style>
