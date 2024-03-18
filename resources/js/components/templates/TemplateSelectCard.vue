<template>
  <div :class="type !== 'wizard' ? 'template-select-card-container pb-2' : 'wizard-select-card-container pb-4'" >
    <div v-if="!showTemplatePreview">
      <wizard-template-card
        v-if="type === 'wizard'"
        :template="template"
        @show-details="showDetails()"
      />
      <screen-template-card
        v-if="type === 'screen'"
        :template="template"
        :isActive="isActive"
        :default-template-id="defaultTemplateId"
        @show-template-preview="showPreview"
        @template-selected="handleSelectedTemplate"
        @template-default-selected="handleDefaultTemplateSelected"
        @reset-default-template="handleResetDefaultTemplate"
      />
      <default-template-card
        v-else
        :template="template"
        @show-details="showDetails()"
      />
    </div>
    <preview-template v-if="showTemplatePreview"></preview-template>
  </div>
</template>

<script>
import WizardTemplateCard from "./WizardTemplateCard.vue";
import ScreenTemplateCard from "./ScreenTemplateCard.vue";
import DefaultTemplateCard from "./DefaultTemplateCard.vue";
import PreviewTemplate from "./PreviewTemplate.vue";

export default {
  components: { WizardTemplateCard, DefaultTemplateCard, ScreenTemplateCard, PreviewTemplate },
  props: ["template", "type", "isActive", "defaultTemplateId"],
  data() {
    return {
      showTemplatePreview: false,
      selectedTemplate: null,
      selectedTemplateId: null,
    };
  },
  methods: {
    showDetails() {
      this.$emit("show-details", { template: this.template, type: this.type });
    },
    showPreview(template) {
      this.$emit("show-template-preview", { template: template, type: this.type});
    },
    handleSelectedTemplate(templateId) {
      this.$emit("selected-template", templateId);
    },
    handleDefaultTemplateSelected(templateId) {
      this.$emit("selected-default-template", templateId);
    },
    handleResetDefaultTemplate() {
      this.$emit("reset-default-template");
    },
  },
};
</script>

<style lang="scss" scoped>
.template-select-card-container {
  flex: 0 0 33.333333%;
}

.wizard-select-card-container {
  height: 240px;
  margin-right: 1rem;
  margin-top: 1rem;
  width: 350px;
}
</style>
