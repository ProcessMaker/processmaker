<template>
  <div>
    <div class="pb-3">
      <b-input-group class="class-align-top" v-if="component === 'template-select-card'">
        <b-input-group-prepend>
          <b-btn class="btn-search-run px-2" :title="$t('Search Templates')" @click="fetch()">
            <i class="fas fa-search search-icon" />
          </b-btn>
        </b-input-group-prepend>
        <b-form-input v-model="filter" id="search-box" class="pl-0" :placeholder="$t('Search Templates')"></b-form-input>
        <b-input-group-append v-if="filter">
          <b-btn
            class="px-1"
            variant="outline-secondary"
            @click="clearSearch()"
          >
            <b-icon icon="x" />
          </b-btn>
        </b-input-group-append>
      </b-input-group>
    </div>
    <div class="cards-container" :class="type !== 'wizard' ? 'fixed-height' : '' ">
      <b-card-group v-if="showTemplateOptionsActionBar && component === 'template-select-card' " id="template-options" deck class="d-flex small-deck-margin">
        <button-card
          class="col-4 p-0"
          :button="blankProcessButton"
          @show-details="showDetails($event)"
          @card-button-clicked="$emit('blank-process-button-clicked')"
        />

        <div v-if="packageAi" class="col-8 p-0">
          <button-card
            :button="aiProcessButton"
            @show-details="showDetails($event)"
            @card-button-clicked="$emit('ai-process-button-clicked')"
          />
        </div>
        <div class="d-flex w-100 align-items-center my-3 card-separator">
          <small class="mr-2 text-secondary">{{ $t('Templates') }}</small>
          <div class="flex-grow-1 border-bottom"></div>
        </div>

      </b-card-group>

      <div class="pb-2 template-container">
        <template v-if="noResults && type !== 'wizard'">
          <div class="no-data-icon d-flex d-block justify-content-center pb-2">
            <i class="fas fa-umbrella-beach mt-5" />
          </div>
          <div class="no-data d-block d-flex justify-content-center">
            {{ $t('No Data Available') }}
          </div>
        </template>
        <template v-else-if="noResults && type == 'wizard'">
          <div class="d-flex justify-content-center my-5">
            <img
              class="image d-flex"
              src="/img/processes-catalogue-empty.svg"
              alt="recent projects"
            >
          </div>
          <h4 class="text-center">
            {{ $t("Currently, there are no Guided Templates available.") }}
          </h4>
          <p class="text-center">
            {{ $t('Please check back soon.') }}
          </p>
        </template>
        <template v-else>
          <b-card-group id="template-options" deck class="small-deck-margin template-options" :class="type !== 'wizard' ?  'd-flex' : ''">
            <template-select-card
              v-show="component === 'template-select-card'"
              v-for="(template, index) in templates"
              :type="type"
              :key="index"
              :template="template"
              @show-details="showDetails($event)"
            />
          </b-card-group>
        </template>
        <template-details v-if="component === 'template-details'" :template="template"></template-details>
        <wizard-template-details v-if="showWizardTemplateDetails" ref="wizardTemplateDetails" :template="template"></wizard-template-details>
      </div>
    </div>
    <template v-if="component !== 'template-details'">
      <div class="d-flex justify-content-between align-items-center">
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
        <div v-if="showTemplateGalleryLink">
          <a href="https://www.processmaker.com/resources/customer-success/templates/" 
            class="text-muted"
            target="_blank">
            {{ $t("Visit our Gallery for more Templates") }}
          </a>
          <i class="ml-1 fas fa-external-link-alt text-muted"></i>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import ButtonCard from "./ButtonCard.vue";
import TemplateSelectCard from "./TemplateSelectCard.vue";
import TemplateDetails from "./TemplateDetails.vue";
import datatableMixin from "../../components/common/mixins/datatable";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import WizardTemplateDetails from "./WizardTemplateDetails.vue";

export default {
  components: { ButtonCard, TemplateSelectCard, TemplateDetails, WizardTemplateDetails },
  mixins: [datatableMixin, dataLoadingMixin],
  props: ["type", "component", "packageAi", 'showTemplateGalleryLink', 'showTemplateOptionsActionBar'],
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
      blankProcessButton: {
        title: this.$t("Build Your Own"),
        icon: "fa fa-plus",
        iconStyle: "font-size: 2em;",
      },
      aiProcessButton: {
        title: this.$t("Generate from AI"),
        helperEnabled: true,
        helperTitle: this.$t("Try our new Generative AI"),
        helperDescription: this.$t("Describe your process. Our AI will build the model for you. Use it immediately or tweak it as needed."),
        svgIcon: "../../../img/nl-to-process.svg",
        svgIconStyle: "height: 2em;",
        showAiSlogan: true,
      },
      showWizardTemplateDetails: false,
    };
  },
  computed: {
    hasGuidedTemplateParams() {
      return window.location.search.includes('?guided_templates=true&template=');
    }
  },
  watch: {
    currentPage() {
      this.fetch();
    },
    perPage() {
      this.fetch();
    },
  },
  methods: {
    async loadData() {
      await this.fetch();

      // After fetch is completed, check if guided template params exist in the URL.
      // This is used when the URL is directly loaded, and we need to target the specified template to show.
      if (this.hasGuidedTemplateParams) {
        this.showDetails();
      }
    },
    async fetch() {
      this.loading = true;
      this.apiDataLoading = true;
      this.orderBy = this.orderBy === "__slot:name" ? "name" : this.orderBy;

      let url =
          this.status === null || this.status === "" || this.status === undefined
              ? "templates/" + this.type.toLowerCase() +"?"
              : "templates/" + this.type.toLowerCase() + "?status=" + this.status + "&";

      // If the type is 'wizard', override the URL to fetch guided templates
      if (this.type === 'wizard') {
        url = 'wizard-templates?';
      }
      // Load from our api client
      await ProcessMaker.apiClient
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
            this.noResults = false;
            }
        })
        .finally(() => {
          this.loading = false;
        });
    },
    clearSearch() {
      this.filter = "";
      this.fetch();
    },
    showDetails($event = null) {
      if (!$event) {
        // Load details directly from the URL template parameter
        const params = new URL(window.location).searchParams;
        const templateId = params.get("template");

        if (templateId) {
          this.loadTemplateDetails(templateId);
        }
      } else if ($event && $event.type === "wizard") {  // Handle different scenarios based on $event type 
        // Add template parameter to the URL if guided templates are selected
        let url = new URL(window.location.href);
        if (url.search.includes('?guided_templates=true')) {
          url.searchParams.append('template', $event.template.unique_template_id);
          history.pushState(null, '', url); // Update the URL without triggering a page reload
        }

        // Direct selection of a wizard template card
        this.loadTemplateDetails($event.template.unique_template_id);
      } else {
        // Direct selection of a default template card
        this.emitTemplateDetails($event.template);
      }
    },
    loadTemplateDetails(uniqueTemplateId) {
      this.template = this.templates.find(template => template.unique_template_id === uniqueTemplateId);
      this.showWizardTemplateDetails = true;

      this.$nextTick(() => {
        this.$refs.wizardTemplateDetails.show();
      });
    },
    emitTemplateDetails(template) {
      this.$emit('show-details', {
        'id': template.id,
        'name': template.name,
        'description': template.description,
        'category_id': template.process_category_id,
        'version': template.version,
      });

      this.template = template;
    }
  },
  beforeMount() {
    this.loadData();
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

.no-data {
  font-size: 1.75rem;
}

.no-data-icon {
  font-size: 5em;
  color: #b7bfc5;
}
.small-deck-margin {
  margin-left: -9px;
  margin-right: -9px;
}
.card-separator {
  margin-left: 0.7rem;
  margin-right: 0.7rem;
}
.cards-container {
  &.fixed-height {
    overflow-y: auto;
    overflow-x: hidden;
    height: 415px;
  }
}
.template-options {
  display: flex;
  flex-wrap: wrap;
  padding-left: 0;
}
.class-align-top {
  margin-top: 15px;
}
</style>
