<template>
  <div>
    <div class="pb-2">
      <b-input-group v-if="component === 'template-select-card'">
        <b-input-group-prepend>
          <b-btn class="btn-search-run px-2" :title="$t('Search Templates')">
            <i class="fas fa-search search-icon"></i>
          </b-btn>
        </b-input-group-prepend>
        <b-form-input v-model="filter" id="search-box" class="pl-0" :placeholder="$t('Search Templates')"></b-form-input>
      </b-input-group>
    </div>
    <div class="pb-2 template-container" >
      <b-card-group deck class="d-flex">
        <template-select-card v-show="component === 'template-select-card'" v-for="(template, index) in templates" :key="index" :template="template" @show-details="showDetails($event)"/>
      </b-card-group>
      <template-details v-if="component === 'template-details'" :template="template"></template-details>
    </div>
  </div>
</template>

<script>
import TemplateSelectCard from "./TemplateSelectCard.vue";
import TemplateDetails from "./TemplateDetails.vue";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";

export default {
  components: { TemplateSelectCard, TemplateDetails},
  mixins: [datatableMixin, dataLoadingMixin],
  props: ['type', 'component'],
  data() {
    return {
      filter: "",
      templates: [],
      currentdata: [],
      template: {},
    };
  },
  computed: {},
  watch: {
    filter() {
      this.fetch();
    }
  },
  beforeMount() {},
  methods: {
    showDetails($event) {
      this.$emit('show-details', {'title': $event.template.name});
      this.template = $event.template;
    },
    fetch() {
        this.loading = true;
        this.apiDataLoading = true;
        this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

        let url =
            this.status === null || this.status === "" || this.status === undefined
                ? "templates/" + this.type.toLowerCase() +"?"
                : "templates/" + this.type.toLowerCase() + "?status=" + this.status + "&";

        // Load from our api client
        ProcessMaker.apiClient
            .get(
                url +
                "page=" +
                this.page +
                "&per_page=" +
                this.perPage +
                "&filter=" +
                this.filter +
                "&order_by=" +
                this.orderBy +
                "&order_direction=" +
                this.orderDirection +
                "&include=user"
            )
            .then(response => {
              this.templates = response.data.data;
              this.apiDataLoading = false;
              this.apiNoResults = false;
              this.loading = false;
            });
      },
  },
  mounted() {
    this.fetch();
  }
};
</script>

<style lang="scss" scoped>
.btn-search-run {
  background-color: #ffffff;
  border-color: #b6bfc6;
  border-right: 0;
  border-radius: 4px;
}

.btn-search-run:active,
  .btn-search-run:focus {
    border-right-width: 0;
    box-shadow: none !important;
    outline: 0 !important;
  }

.search-icon {
  color: #6C757D;
}

.template-container {
  height: 567px;
  overflow-x: hidden;
  overflow-y: auto;
}
</style>
