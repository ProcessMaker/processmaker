<template>
  <div class="template-select-card-container" :class="type !== 'wizard' ? 'pb-2' : 'pb-4'">
    <wizard-template-card
      v-if="type === 'wizard'"
      :template="template"
      @show-details="showDetails()" 
    />
    <default-template-card
      v-else
      :template="template"
      @show-details="showDetails()" 
    />
  </div>
</template>

<script>
import WizardTemplateCard from './WizardTemplateCard';
import DefaultTemplateCard from './DefaultTemplateCard';

export default {
  components: {WizardTemplateCard, DefaultTemplateCard},
  props: ["template", "type"],
  data() {
    return {
    };
  },
  methods: {
    showDetails() {
      this.$emit("show-details", { template: this.template });
    },
  },
};
</script>

<style lang="scss" scoped>
.template-select-card-container {
  flex: 0 0 33.333333%;
}
</style>
