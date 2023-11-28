<template>
  <div class="pb-2 template-select-card-container">
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
.template-select-card {
  // width: 292px;
  height: 172px;
  border-radius: 4px;
  padding: 10px 8px 10px 8px;
  overflow: hidden;
  border: 2px solid rgba(0, 0, 0, 0.125);
}

.card-title {
  font-weight: 600;
  font-size: 14px;
}

.card-img {
  background: #80808017;
  height: 112px;
  display: flex;
  align-items: center;
}

.card-body {
  padding: 2px!important;
}

.card-text {
  font-size: 12px;
  color: #6C757D;
}

.category-badge {
  background-color: #DEEBFF;
  color: #104A75;
  font-size: 12px;
}

.wizard-card {
  border-radius: 16px;
  border: 1px solid #CDDDEE;
  background-size: cover;
  height: 243px;
  .card-body {
    padding: 10px !important;
  }
}

.wizard-card:hover {
  box-shadow: 0px 10px 20px 4px #00000021;
}

.hover {
  border-color: #1572C2;
  cursor: pointer;
}

@media (min-width: 576px) {
  .card-deck .card {
    margin-left: 9px;
    margin-right: 9px;
  }
}

</style>
