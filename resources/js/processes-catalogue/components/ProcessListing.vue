<template>
  <div class="process-listing h-100">
    <CardProcess
      :key="index"
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
  data(){
    return {index:0};
  },
  watch: {
    categoryId(nVal,oVal){
      if(nVal !== oVal){
        this.index++;
      }
    }
  },
  computed: {
    isTemplateCategory() {
      return ['guided_templates'].includes(this.categoryId)
    },
  }
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