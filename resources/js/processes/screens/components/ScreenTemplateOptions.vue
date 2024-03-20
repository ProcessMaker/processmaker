<template>
  <div>
    <template-type-dropdown
      v-model="templateType"
      @selected-template="handleSelectedTemplateType"
    />
    <data-loading
      v-show="shouldShowLoader"
      :for="/templates\screen/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="cards-container">
      <b-card-group
        v-cloak
        id="screen-template-options"
        deck
        class="screen-template-options justify-content-space-between"
      >
        <template-select-card
          v-for="(template, index) in screenTemplates"
          :key="index"
          :type="type"
          :template="template"
          :default-template-id="defaultTemplateId"
          :default-template-screen-type="selectedScreenType"
          :is-default-template-public="isDefaultTemplatePublic"
          :isActive="selectedTemplateId === template.id ? 'active' : ''"
          @show-template-preview="showPreview"
          @selected-template="handleSelectedTemplate"
          @selected-default-template="handleSelectedDefaultTemplate"
        />
      </b-card-group>
    </div>
  </div>
</template>

<script>
import TemplateTypeDropdown from "./TemplateTypeDropdown.vue";
import TemplateSelectCard from "../../../components/templates/TemplateSelectCard.vue";
import datatableMixin from "../../../components/common/mixins/datatable";
import dataLoadingMixin from "../../../components/common/mixins/apiDataLoading";

export default {
  components: { TemplateTypeDropdown, TemplateSelectCard },
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["selectedScreenType"],
  data() {
    return {
      filter: "",
      screenTemplates: [],
      blankTemplate: [
        {
          screen_type: this.selectedScreenType,
          name: "Blank Template",
          description: "Creates a blank screen.",
          is_public: this.templateType,
        },
      ],
      type: "screen",
      templateType: "",
      defaultScreenType: "FORM",
      template: {},
      selectedTemplateId: null,
      defaultTemplateId: null,
    };
  },
  computed: {
    isDefaultTemplatePublic() {
      return this.templateType === 'Public Templates' ? 1 : 0;
    }
  },
  watch: {
    selectedScreenType() {
      this.fetch();
    },
    templateType(newVal) {
      this.fetch(newVal);
      this.$emit('default-template-type-changed', newVal);
    },
  },
  mounted() {
    this.fetch();
  },
  methods: {
    getDefaultTemplates() {
      const defaultTemplate = this.screenTemplates.find((template) => template.is_default_template === 1 && template.screen_type == this.selectedScreenType && template.hasOwnProperty('id') && template.is_public === this.isDefaultTemplatePublic);
      if (defaultTemplate) {
        this.defaultTemplateId = defaultTemplate.id;
      } else {
        this.defaultTemplateId = null;
      }
    },
    handleSelectedTemplateType(templateType) {
      this.templateType = templateType;
    },
    fetch() {
      let url;

      if (this.templateType === "") {
        this.templateType = "Public Templates";
      }

      this.loading = true;
      this.apiDataLoading = true;
      this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

      if (this.templateType === "Public Templates") {
        url = `templates/screen?screen_type=${this.selectedScreenType}&is_public=1`;
      } else if (this.templateType === "My Templates") {
        url = `templates/screen?screen_type=${this.selectedScreenType}&is_public=0`;
      }

      // Load from our API client
      ProcessMaker.apiClient
        .get(
          url +
          "&per_page=1000" +
          "&filter=" +
          this.filter +
          "&order_by=" +
          this.orderBy +
          "&order_direction=" +
          this.orderDirection
        )
        .then(response => {
          this.blankTemplate[0].screen_type = this.selectedScreenType;
          this.blankTemplate[0].is_public = this.isDefaultTemplatePublic;
          this.screenTemplates = this.blankTemplate.concat(response.data.data);
          this.apiDataLoading = false;
          this.apiNoResults = false;
          this.getDefaultTemplates();
        })
        .finally(() => {
          this.loading = false;
        });
    },
    showPreview(template) {
      this.$emit("show-template-preview", template);
    },
    handleSelectedTemplate(templateId) {
      this.$emit("selected-template", templateId);
      this.selectedTemplateId = templateId;
    },
    handleSelectedDefaultTemplate(templateId) {
      this.defaultTemplateId = templateId;

      if (templateId === null) {
        this.setBlankTemplateAsDefault();
        this.updatePreviousDefaultTemplateStatus(templateId);
      } else {
        this.setDefaultTemplate(templateId);
        this.updatePreviousDefaultTemplateStatus(templateId);
      }

      this.emitSelectedDefaultTemplate();
    },
    setBlankTemplateAsDefault() {
      const blankTemplate = this.getBlankTemplate();
      if (blankTemplate) {
        blankTemplate.is_default_template = 1;
        this.updateTemplateInArray(blankTemplate);
      }
    },
    setDefaultTemplate(templateId) {
      const defaultTemplate = this.getTemplateById(templateId);
      if (defaultTemplate) {
        defaultTemplate.is_default_template = 1;
        this.updateTemplateInArray(defaultTemplate);
      }
    },
    updatePreviousDefaultTemplateStatus(currentTemplateId) {
      const previousDefaultTemplate = this.getPreviousDefaultTemplate(currentTemplateId);
      if (previousDefaultTemplate) {
        previousDefaultTemplate.is_default_template = 0;
        this.updateTemplateInArray(previousDefaultTemplate);
      }
    },
    emitSelectedDefaultTemplate() {
      this.$emit("selected-default-template", this.defaultTemplateId);
    },
    getBlankTemplate() {
      return this.screenTemplates.find(template => !template.hasOwnProperty('id') && template.screen_type == this.selectedScreenType && template.is_public === this.isDefaultTemplatePublic);
    },
    getTemplateById(templateId) {
      return this.screenTemplates.find(template => template.id === templateId && template.screen_type == this.selectedScreenType && template.is_public === this.isDefaultTemplatePublic);
    },
    getPreviousDefaultTemplate(currentTemplateId) {
      return this.screenTemplates.find(template =>
        (template.id !== currentTemplateId && template.is_default_template === 1 && template.screen_type == this.selectedScreenType && template.is_public === this.isDefaultTemplatePublic) ||
        (!template.hasOwnProperty('id') && template.is_default_template === 1 && template.screen_type == this.selectedScreenType && template.is_public === this.isDefaultTemplatePublic)
      );
    },
    updateTemplateInArray(updatedTemplate) {
      const index = this.screenTemplates.indexOf(updatedTemplate);
      this.$set(this.screenTemplates, index, updatedTemplate);
    },
  },
};
</script>

<style lang="scss" scoped>
  .cards-container {
    display: flex;
    height: 500px;
    overflow-y: auto;
    overflow-x: hidden;
    margin-top: 20px;
  }
</style>
