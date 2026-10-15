<template>
  <div class="template-select-card-container pb-2">
    <div v-if="!showTemplatePreview">
      <screen-template-card
        v-if="type === 'screen'"
        :template="template"
        :is-active="isActive"
        :default-template-id="defaultTemplateId"
        :default-template-screen-type="defaultTemplateScreenType"
        :is-default-template-public="isDefaultTemplatePublic"
        @show-template-preview="showPreview"
        @template-selected="handleSelectedTemplate"
        @template-default-selected="handleDefaultTemplateSelected"
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
import ScreenTemplateCard from "./ScreenTemplateCard.vue";
import DefaultTemplateCard from "./DefaultTemplateCard.vue";
import PreviewTemplate from "./PreviewTemplate.vue";

export default {
  components: { DefaultTemplateCard, ScreenTemplateCard, PreviewTemplate },
  props: ["template", "type", "isActive", "defaultTemplateId", "defaultTemplateScreenType", "isDefaultTemplatePublic"],
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
  },
};
</script>

<style lang="scss" scoped>
.template-select-card-container {
  flex: 0 0 33.333333%;
}

</style>
