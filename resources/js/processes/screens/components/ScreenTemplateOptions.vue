<template>
  <div>
    <template-type-dropdown
      v-model="templateType"
      @selected-template="handleSelectedTemplate"
    />
    <b-card-group
      id="screen-template-options"
      deck
      class="screen-template-options"
    >
      <template-select-card
        v-for="(template, index) in screenTemplates"
        :key="index"
        :type="type"
        :template="template"
      />
    </b-card-group>
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
  props: [],
  data() {
    return {
      filter: "",
      screenTemplates: [],
      type: "screen",
      templateType: "",
      template: {},
    };
  },
  watch: {
    templateType(newVal) {
      this.fetch(newVal);
    },
  },
  methods: {
    handleSelectedTemplate(templateType) {
      this.templateType = templateType;
    },
    fetch() {
      this.loading = true;
      this.apiDataLoading = true;
      this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

      const url = this.templateType === "Public Templates"
        ? "templates/screen?is_public=true"
        : "templates/screen?is_public=0";

      // Load from our API client
      ProcessMaker.apiClient
        .get(
          url +
          "&filter=" +
          this.filter +
          "&order_by=" +
          this.orderBy +
          "&order_direction=" +
          this.orderDirection
        )
        .then(response => {
          this.screenTemplates = response.data.data;
          this.apiDataLoading = false;
        })
        .finally(() => {
          this.loading = false;
        });
    },
  },
};
</script>
