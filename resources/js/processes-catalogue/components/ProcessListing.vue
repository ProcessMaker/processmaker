<template>
  <div>

    <CardProcess
      v-if="!isTemplateCategory"
      :categoryId="categoryId"
    />

    <wizard-templates
        v-if="isTemplateCategory"
        :categoryId="categoryId"
    />

  </div>
</template>

<script>
import CardProcess from "./CardProcess.vue";
import WizardTemplates from "./WizardTemplates.vue";
export default {
  props: ["categoryId"],
  components: {
    WizardTemplates,
    CardProcess,
  },
  computed: {
    isTemplateCategory() {
      return ['all_templates', 'guided_templates'].includes(this.categoryId)
    },
  }
};
</script>