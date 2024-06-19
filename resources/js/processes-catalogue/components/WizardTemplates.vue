<template>
  <div class="wizard-template-container">
    <template-search
      id="wizard-search"
      type="wizard"
      package-ai="false"
      :component="currentComponent"
      :show-template-options-action-bar="false"
      @show-details="showDetails($event)"
    />
  </div>
</template>

<script>
import TemplateSearch from "../../components/templates/TemplateSearch.vue";

export default {
  components: { TemplateSearch },
  props: ["categoryId"],
  data() {
    return {
      currentComponent: "template-select-card",
      templateData: {},
    };
  },
  methods: {
    showDetails($event) {
      this.templateData = $event;
      this.currentComponent = "template-details";
    },
  },
};
</script>

<style>
#wizard-search  #search-box,
#wizard-search .input-group-prepend .btn-search-run {
  border: none;
  background-color: #f7f9fb;
}
</style>
