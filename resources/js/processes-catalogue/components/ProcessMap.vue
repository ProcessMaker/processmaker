<template>
  <div id="processMap">
    <div id="processData">
      <div
        id="header"
        class="d-flex justify-content-between mb-3"
      >
        <h4 class="d-flex align-items-center">
          <i
            class="fas fa-arrow-circle-left text-secondary mr-2 iconTitle"
            @click="goBack"
          />
          <span class="ml-2 title-process">{{ process.name }}</span>
        </h4>
        <span class="border bg-white rounded-circle d-flex align-items-center p-0 ellipsis-border">
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
      </div>
      <div>
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
import EllipsisMenu from "../../components/shared/EllipsisMenu.vue";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../components/pm-blocks/CreatePmBlockModal.vue";
import AddToProjectModal from "../../components/shared/AddToProjectModal.vue";
import ellipsisMenuMixin from "../../components/shared/ellipsisMenuActions";
import processNavigationMixin from "../../components/shared/processNavigation";
import ModalSaveVersion from "../../components/shared/ModalSaveVersion.vue";

export default {
  components: {
    EllipsisMenu,
    CreateTemplateModal,
    CreatePmBlockModal,
    AddToProjectModal,
    ModalSaveVersion,
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
      largeDescription: false,
      readActivated: false,
      showEllipsis: false
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
  },
  methods: {
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
  font-size: 32px;
  cursor: pointer;
}
.title-process {
  color: #556271;
  font-size: 21px;
  font-weight: 600;
  letter-spacing: -0.42px;
}
.description {
  color: #4F606D;
  font-size: 16px;
  font-weight: 400;
  letter-spacing: -0.32px;
}
.ellipsis-border{
  border-color: #CDDDEE;
}
.read-more {
  cursor: pointer;
  color: #1572C2;
}
</style>
