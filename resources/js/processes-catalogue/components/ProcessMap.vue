<template>
  <div id="processMap">
    <div id="processData">
      <div
        id="header"
        class="d-flex justify-content-between mb-3"
      >
        <h4 class="d-flex align-items-center">
          <i
            class="fas fa-arrow-circle-left text-secondary mr-2 title-font"
            @click="goBack"
          />
          {{ process.name }}
        </h4>
        <span class="border bg-white rounded-circle d-flex align-items-center p-0 ellipsis-border">
          <ellipsis-menu
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
      <p> {{ process.description }}</p>
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
      :descriptionSettings="process.description"
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
import ModalSaveVersion from "../../components/shared/ModalSaveVersion.vue"

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
    };
  },
  mounted() {
    this.getActions();
    this.optionsData = {
      id: this.process.id.toString(),
      type: "Process",
    };
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
      this.processLaunchpadActions = this.processActions.filter((action) => action.value !== "open-launchpad");
    },
    /** Rerun a process cards from process info */
    goBack() {
      this.$emit("goBackCategory");
    },
  },
};
</script>

<style scoped>
.title-font {
  font-size: 32px;
  cursor: pointer;
}
.ellipsis-border{
  border-color: #CDDDEE;
}
</style>
