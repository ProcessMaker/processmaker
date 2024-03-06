<template>
  <div id="processCollapseInfo">
    <div id="processData">
      <div 
        id="header"
        class="card card-body"
      >
        <div class="d-flex justify-content-between">
          <div class="d-flex align-items-center">
            <i
              class="fas fa-arrow-left text-secondary mr-2 iconTitle"
              @click="goBack"
            />
            <button
              v-if="infoCollapsed"
              class="btn border-0 title-process-button"
              type="button"
              data-toggle="collapse"
              data-target="#collapseProcessInfo"
              aria-expanded="true"
              aria-controls="collapseProcessInfo"
              @click="toogleInfoCollapsed()"
            >
              {{ $t('Process Info') }}
              <i class="fas fa-angle-up pl-2" />
            </button>
            <button
              v-else
              class="btn border-0 title-process-button"
              type="button"
              data-toggle="collapse"
              data-target="#collapseProcessInfo"
              aria-expanded="false"
              aria-controls="collapseProcessInfo"
              @click="toogleInfoCollapsed()"
            >
              {{ process.name }}
              <i class="fas fa-angle-down pl-2" />
            </button>
          </div>
          <div class="d-flex align-items-center">
            <div class="card-bookmark mx-2">
              <i
                :ref="`bookmark-${process.id}-marked`"
                v-b-tooltip.hover.bottom
                :title="$t(labelTooltip)"
                class="fas fa-bookmark"
                :class="{ marked: showBookmarkIcon, unmarked: !showBookmarkIcon  }"
                @click="checkBookmark(process)"
              />
            </div>
            <span class="ellipsis-border">
              <ellipsis-menu
                v-if="showEllipsis"
                :actions="processLaunchpadActions"
                :permission="permission"
                :data="process"
                :is-documenter-installed="isDocumenterInstalled"
                :divider="false"
                :lauchpad="true"
                variant="none"
                @navigate="onProcessNavigate"
              />
            </span>
            <buttons-start :process="process" />
          </div>
        </div>
      </div>
      <div class="collapse show" id="collapseProcessInfo">
        <div class="info-collapse">
          <p class="title-process">
            {{ process.name }}
          </p>
          <p
            v-if="readActivated || !largeDescription"
            class="description"
          >
            {{ process.description }}
          </p>
          <p
            v-if="!readActivated && largeDescription"
            class="description"
          >
            {{ process.description.slice(0,300) }}
            <a
              v-if="!readActivated"
              class="read-more"
              @click="activateReadMore"
            >
              ...
            </a>
          </p>  
          <div
            class="d-flex"
          >
            <b-col cols="9">
              <processes-carousel
                :process="process"
              />
            </b-col>
            <b-col cols="3">
              <process-options :process="process" />
            </b-col>
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
    <modal-save-version
      id="modal-save-version"
      ref="modal-save-version"
      asset-type="process"
      origin="core"
      :options="optionsData"
      :description-settings="process.description"
      :process="process"
    />
  </div>
</template>

<script>
import ButtonsStart from "./optionsMenu/ButtonsStart.vue";
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import ModalSaveVersion from "../../components/shared/ModalSaveVersion.vue";
import ProcessesCarousel from "./ProcessesCarousel.vue";
import ProcessOptions from "./ProcessOptions.vue";

export default {
  components: {
    ButtonsStart,
    EllipsisMenu,
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    ModalSaveVersion,
    ProcessOptions,
    ProcessesCarousel,
  },
  mixins: [ellipsisMenuMixin, processNavigationMixin],
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId"],
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
    this.verifyDescription();
    this.bookmarkIcon();
  },
  methods: {
    /** 
     * Change button title
     */
    toogleInfoCollapsed() {
      this.infoCollapsed = !this.infoCollapsed;
    },
    /**
     * Verify if the process is marked
     */
    bookmarkIcon() {
      this.labelTooltip = this.process.bookmark_id !== 0 ? 
        this.$t("Remove from My Bookmarks") : this.$t("Add to My Bookmarks");
      this.showBookmarkIcon = this.process.bookmark_id !== 0;
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
      this.$refs["modal-save-version"].showModal();
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
  },
};
</script>

<style scoped>
.iconTitle {
  font-size: 18px;
  cursor: pointer;
}
.title-process-button {
  color: #6a7888;
  font-family: 'Open Sans', sans-serif;
  font-size: 14px;
  font-weight: 700;
  line-height: 21px;
  letter-spacing: -0.02em;
  text-align: left;
  background-color: white;
}
.title-process-button:hover {
  color: #1572c2;
}
.info-collapse {
  padding: 32px;
  padding-bottom: 0px;
  background-color: white;
}
.title-process {
  color: #556271;
  font-family: 'Open Sans', sans-serif;
  font-size: 25px;
  font-weight: 400;
  line-height: 32px;
  letter-spacing: -0.02em;
  text-align: left;
}
.description {
  color: #4f606d;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 24px;
  letter-spacing: -0.02em;
  text-align: left;
}
.ellipsis-border{
  display: flex;
  justify-content: center;
  align-items: center;
  background-color: white;
  border: 1px solid #CDDDEE;
  border-radius: 19px;
  width: 32px;
  height: 32px;
  margin: 0px 8px;
}
.read-more {
  cursor: pointer;
  color: #1572C2;
}
.card-bookmark {
  float: right;
  font-size: 24px;
}
.card-bookmark:hover {
  cursor: pointer;
}
#header {
  padding: 12px 16px;
  border-bottom: 1px solid #CDDDEE;
  box-shadow: 0px 6px 18px 0px #EFF1F4;
}
.marked {
  color: #f5bC00;
}
.unmarked {
  color: #ebf3f7;
  -webkit-text-stroke-color: #bed1e5;
  -webkit-text-stroke-width: 1px;
}
.unmarked:hover {
  color: #ffd445;
  -webkit-text-stroke-width: 0;
}
</style>
