<template>
  <div>
    <b-button
      v-if="!hideAddBtn"
      v-b-modal.selectTemplate
      :aria-label="createProcess"
      class="mb-3 mb-md-0 ml-md-2"
    >
      <i class="fas fa-plus" /> {{ process }}
    </b-button>
    <modal
      id="selectTemplate"
      size="huge"
      :title="title"
      :subtitle="subtitle"
      :has-header-buttons="hasHeaderButtons"
      :header-buttons="headerButtons"
      :has-title-buttons="hasTitleButtons"
      :title-buttons="titleButtons"
      :hide-footer="true"
      @showSelectTemplate="showSelectTemplateComponent"
      @createBlankProcess="createBlankProcess"
      @useSelectedTemplate="useSelectedTemplate"
      @ok.prevent="onSubmit"
      @close="close"
    >
      <template-search
        :type="type"
        :component="currentComponent"
        :show-template-options-action-bar="true"
        :package-ai="packageAi"
        @show-details="updateModal($event)"
        @blank-process-button-clicked="createBlankProcess()"
        @ai-process-button-clicked="createAiProcess()"
      />
    </modal>
    <create-process-modal
      ref="create-process-modal"
      :blank-template="blankTemplate"
      :count-categories="countCategories"
      :selected-template="selectedTemplate"
      :template-data="templateData"
      :is-projects-installed="isProjectsInstalled"
      :project-id="projectId"
      @resetModal="resetModal()"
    />
  </div>
</template>

<script>
import { Modal } from "../shared";
import TemplateSearch from "./TemplateSearch.vue";
import CreateProcessModal from "../../processes/components/CreateProcessModal.vue";

export default {
  components: { Modal, TemplateSearch, CreateProcessModal },
  props: ["type", "countCategories", "packageAi", "isProjectsInstalled", "hideAddBtn", "projectId"],
  data() {
    return {
      title: "",
      showHelper: true,
      currentComponent: "template-select-card",
      hasTitleButtons: true,
      hasHeaderButtons: false,
      headerButtons: [
        {
          content: "< Back", action: "showSelectTemplate", variant: "link", disabled: false, hidden: true, ariaLabel: "Back to select templates",
        },
      ],
      titleButtons: [
        {
          content: "Use Template", action: "useSelectedTemplate", variant: "primary", disabled: false, hidden: true, position: "right", ariaLabel: `Create a ${this.type} with this template`,
        },
      ],
      blankTemplate: false,
      selectedTemplate: false,
      templateData: {},
    };
  },
  computed: {
    subtitle() {
      return this.$t("Start a new process from a blank canvas, a text description, or a preset template.");
    },
    createProcess() {
      return this.$t("Create Process");
    },
    process() {
      return this.$t("Process");
    },
  },
  mounted() {
    this.title = this.$t(`New ${this.type}`);
    const searchParams = new URLSearchParams(window.location.search);
    if (searchParams.size > 0 && searchParams.get("new") === "true") {
      this.show();
    }
  },
  methods: {
    updateModal($event) {
      this.templateData = $event;
      this.title = $event.name;
      this.hasHeaderButtons = true;
      this.headerButtons[0].hidden = false;
      this.titleButtons[0].hidden = false;
      this.currentComponent = "template-details";
    },
    showSelectTemplateComponent() {
      this.currentComponent = "template-select-card";
      this.headerButtons[0].hidden = true;
      this.titleButtons[0].hidden = true;
      this.hasHeaderButtons = false;
      this.title = this.$t(`New ${this.type}`);
    },
    createBlankProcess() {
      this.selectedTemplate = false;
      this.blankTemplate = true;
      this.$bvModal.hide("selectTemplate");
      this.$refs["create-process-modal"].show();
    },
    createAiProcess() {
      window.location.href = "/package-ai/processes/create";
    },
    useSelectedTemplate() {
      this.selectedTemplate = true;
      this.blankTemplate = false;
      this.$bvModal.hide("selectTemplate");
      this.showSelectTemplateComponent();
      this.$refs["create-process-modal"].show();
    },
    close() {
      this.$bvModal.hide("selectTemplate");
      this.showSelectTemplateComponent();
    },
    resetModal() {
      this.selectedTemplate = false;
      this.templateData = {};
    },
    show() {
      this.$bvModal.show("selectTemplate");
    },
  },
};
</script>

<style scoped>

</style>
