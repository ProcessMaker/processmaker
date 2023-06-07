<template>
  <div>
    <div class="pb-3">
      <b-input-group v-if="component === 'template-select-card'">
        <b-input-group-prepend>
          <b-btn class="btn-search-run px-2" :title="$t('Search Templates')">
            <i class="fas fa-search search-icon" />
          </b-btn>
        </b-input-group-prepend>
        <b-form-input v-model="filter" id="search-box" class="pl-0" :placeholder="$t('Search Templates')"></b-form-input>
      </b-input-group>
    </div>
    <div class="pb-2 template-container">
      <template v-if="noResults === true">
        <div class="no-data-icon d-flex d-block justify-content-center mt-5 pt-5 pb-2">
          <i class="fas fa-umbrella-beach mt-5 pt-5" />
        </div>
        <div class="no-data d-block d-flex justify-content-center">
          {{ $t('No Data Available') }}
        </div>
      </template>
      <template v-else>
        <b-card-group id="template-options" deck class="d-flex">
          <template-select-card
            v-show="component === 'template-select-card'"
            v-for="(template, index) in templates"
            :key="index"
            :template="template"
            @show-details="showDetails($event)"
          />
        </b-card-group>
      </template>
      <template-details v-if="component === 'template-details'" :template="template"></template-details>
      <template v-else>
          <b-pagination
          v-model="currentPage"
          v-if="templates.length > 0"
          class="template-modal-pagination"
          :total-rows="totalRow"
          :per-page="perPage"
          :limit="limit"
          prev-class="caretBtn prevBtn"
          next-class="caretBtn nextBtn"
          size="sm"
          last-number
          first-number
        ></b-pagination>
      </template>
    </div>
  </div>
</template>

<script>
import TemplateSelectCard from "./TemplateSelectCard.vue";
import TemplateDetails from "./TemplateDetails.vue";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";

export default {
  components: { TemplateSelectCard, TemplateDetails },
  mixins: [datatableMixin, dataLoadingMixin],
  props: ['type', 'component'],
  data() {
    return {
      filter: "",
      templates: [],
      currentdata: [],
      template: {},
      noResults: false,
      currentPage: 1,
      totalRow: null,
      perPage: 18,
      limit: 7,
    };
  },
  watch: {
    currentPage() {
      this.fetch();
    },
    perPage() {
      this.fetch();
    },
    filter() {
      this.fetch();
    },
  },
  beforeMount() {},
  methods: {
    showDetails($event) {
      this.$emit('show-details', {
        'id': $event.template.id, 
        'name': $event.template.name, 
        'description': $event.template.description,
        'category_id': $event.template.process_category_id
      });
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
                this.currentPage +
                "&per_page=" +
                this.perPage + 
                "&filter=" +
                this.filter +
                "&order_by=" +
                this.orderBy +
                "&order_direction=" +
                this.orderDirection +
                "&include=user,categories,category"
            )
            .then(response => {
              if(response.data.data.length === 0) {
                this.noResults = true;
              } else {
                this.templates = response.data.data;
                this.totalRow = response.data.meta.total;
                this.apiDataLoading = false;
                this.apiNoResults = false;
                this.loading = false;
                this.noResults = false;
                }
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
  height: 375px;
  overflow-x: hidden;
  overflow-y: auto;
}

.no-data {
  font-size: 1.75rem;
}

.no-data-icon {
  font-size: 5em;
  color: #b7bfc5;
}
</style>
