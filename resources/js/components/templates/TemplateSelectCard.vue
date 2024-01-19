<template>
  <div :class="type !== 'wizard' ? 'template-select-card-container pb-2' : 'wizard-select-card-container pb-4'" >
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
      this.$emit("show-details", { template: this.template, type: this.type });
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
