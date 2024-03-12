<template>
  <div>
    <template-type-dropdown
      v-model="templateType"
      @selected-template="handleSelectedTemplate"
    />
    <data-loading
      v-show="shouldShowLoader"
      :for="/templates\screen/"
      :empty="$t('No Data Available')"
      :empty-desc="$t('')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="cards-container">
      <b-card-group
        v-cloak
        id="screen-template-options"
        deck
        class="screen-template-options justify-content-space-between"
      >
        <template-select-card
          v-for="(template, index) in screenTemplates"
          :key="index"
          :type="type"
          :template="template"
          @show-template-preview="showPreview"
        />
      </b-card-group>
    </div>
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
  props: ["selectedScreenType"],
  data() {
    return {
      filter: "",
      screenTemplates: [],
      type: "screen",
      templateType: "",
      defaultScreenType: "FORM",
      template: {},
    };
  },
  watch: {
    selectedScreenType() {
      this.fetch();
    },
    templateType(newVal) {
      this.fetch(newVal);
    },
  },
  mounted() {
    this.fetch();
  },
  methods: {
    handleSelectedTemplate(templateType) {
      this.templateType = templateType;
    },
    fetch() {
      let url;

      if (this.templateType === "") {
        this.templateType = "Public Templates";
      }

      this.loading = true;
      this.apiDataLoading = true;
      this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

      if (this.templateType === "Public Templates") {
        url = `templates/screen?screen_type=${this.selectedScreenType}&is_public=1`;
      } else if (this.templateType === "My Templates") {
        url = `templates/screen?screen_type=${this.selectedScreenType}&is_public=0`;
      }

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
          this.apiNoResults = false;
        })
        .finally(() => {
          this.loading = false;
        });
    },
    showPreview(template) {
      this.$emit('show-template-preview', template);
    }

  },
};
</script>

<style lang="scss" scoped>
.cards-container {
  display: flex;
  height: 500px;
  overflow-y: auto;
  overflow-x: hidden;
  margin-top: 20px;
}
</style>
