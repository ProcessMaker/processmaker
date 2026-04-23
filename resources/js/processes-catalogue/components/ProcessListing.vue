<template>
  <div class="process-listing h-100">
    <CardProcess
      v-if="!isTemplateCategory"
      :key="index"
      :category-id="categoryId"
    />

    <wizard-templates
      v-if="isTemplateCategory"
      :category-id="categoryId"
    />
  </div>
</template>

<script>
import CardProcess from "./CardProcess.vue";
import WizardTemplates from "./WizardTemplates.vue";

export default {
  components: {
    WizardTemplates,
    CardProcess,
  },
  props: ["categoryId"],
  data() {
    return { index: 0 };
  },
  computed: {
    isTemplateCategory() {
      return ["guided_templates"].includes(this.categoryId);
    },
  },
  watch: {
    categoryId(nVal, oVal) {
      if (nVal !== oVal) {
        this.index++;
      }
    },
  },
};
</script>

<style lang="scss" scoped>
@import '~styles/variables';

.process-listing {
  @media (max-width: $lp-breakpoint) {
    padding-left: 1em;
    padding-right: 1em;
  }
}
</style>
