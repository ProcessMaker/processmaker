<template>
  <div>
    <div class="pb-3">
      <b-input-group v-if="component === 'template-select-card'">
        <b-input-group-prepend>
          <b-btn class="btn-search-run px-2" :title="$t('Search Templates')" @click="fetch()">
            <i class="fas fa-search search-icon" />
          </b-btn>
        </b-input-group-prepend>
        <b-form-input v-model="filter" id="search-box" class="pl-0" :placeholder="$t('Search Templates')"></b-form-input>
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
        <template v-if="noResults">
          <div class="no-data-icon d-flex d-block justify-content-center pb-2">
            <i class="fas fa-umbrella-beach mt-5" />
          </div>
          <div class="no-data d-block d-flex justify-content-center">
            {{ $t('No Data Available') }}
          </div>
        </template>
        <template v-else>
          <b-card-group id="template-options" deck class="d-flex small-deck-margin template-options">
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
        <template-details v-if="component === 'template-details'" :template="template" :type="type"></template-details>
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

export default {
  components: { ButtonCard, TemplateSelectCard, TemplateDetails },
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
        title: this.$t("Generate from Text"),
        helperEnabled: true,
        helperTitle: this.$t("Try our new Generative AI"),
        helperDescription: this.$t("Describe your process. Our AI will build the model for you. Use it immediately or tweak it as needed."),
        svgIcon: "../../../img/nl-to-process.svg",
        svgIconStyle: "height: 2em;",
        showAiSlogan: true,
      },
    };
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
    showDetails($event) {
      this.$emit('show-details', {
        'id': $event.template.id, 
        'name': $event.template.name, 
        'description': $event.template.description,
        'category_id': $event.template.process_category_id,
        'version' : $event.template.version,
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

        // TODO: Remove this temporary code to populate the wizard templates
        if (this.type === 'wizard') {
          this.templates = [
            {
              id: 1, 
              name: 'New Hire Onboarding', 
              shortDescription: 'Keep a constant pulse on employee engagement',
              headline: 'Gather real-time Data about how your people feel about work',
              description: 'Drive action and measure your impact with a continuous, real-time understanding of employee engagement.',
              icon: 'https://cdn-icons-png.flaticon.com/512/1160/1160358.png',
              backgroundImage: 'https://images.pexels.com/photos/255379/pexels-photo-255379.jpeg?cs=srgb&dl=pexels-miguel-%C3%A1-padri%C3%B1%C3%A1n-255379.jpg&fm=jpg',
              sliderImages: ['https://www.smartinsights.com/wp-content/uploads/2023/11/RACE-Digital-Marketing-Plan-Funnel-2023.png', 'https://blog.hootsuite.com/wp-content/uploads/2021/10/How-to-Create-a-Social-Media-Marketing-Strategy-in-9-Easy-Steps-Free-Template.png'],
              helperProcessId: 1,
              categories:'1',
            },
            {
              id: 1, 
              name: 'New Hire Onboarding', 
              shortDescription: 'Keep a constant pulse on employee engagement',
              headline: 'Gather real-time Data about how your people feel about work',
              description: 'Drive action and measure your impact with a continuous, real-time understanding of employee engagement.',
              icon: 'https://cdn-icons-png.flaticon.com/512/1160/1160358.png',
              backgroundImage: 'https://images.pexels.com/photos/255379/pexels-photo-255379.jpeg?cs=srgb&dl=pexels-miguel-%C3%A1-padri%C3%B1%C3%A1n-255379.jpg&fm=jpg',
              sliderImages: ['https://www.smartinsights.com/wp-content/uploads/2023/11/RACE-Digital-Marketing-Plan-Funnel-2023.png', 'https://blog.hootsuite.com/wp-content/uploads/2021/10/How-to-Create-a-Social-Media-Marketing-Strategy-in-9-Easy-Steps-Free-Template.png'],
              helperProcessId: 1,
              categories:'1',
            },
            {
              id: 1, 
              name: 'New Hire Onboarding', 
              shortDescription: 'Keep a constant pulse on employee engagement',
              headline: 'Gather real-time Data about how your people feel about work',
              description: 'Drive action and measure your impact with a continuous, real-time understanding of employee engagement.',
              icon: 'https://cdn-icons-png.flaticon.com/512/1160/1160358.png',
              backgroundImage: 'https://images.pexels.com/photos/255379/pexels-photo-255379.jpeg?cs=srgb&dl=pexels-miguel-%C3%A1-padri%C3%B1%C3%A1n-255379.jpg&fm=jpg',
              sliderImages: ['https://www.smartinsights.com/wp-content/uploads/2023/11/RACE-Digital-Marketing-Plan-Funnel-2023.png', 'https://blog.hootsuite.com/wp-content/uploads/2021/10/How-to-Create-a-Social-Media-Marketing-Strategy-in-9-Easy-Steps-Free-Template.png'],
              helperProcessId: 1,
              categories:'1',
            },
            {
              id: 1, 
              name: 'New Hire Onboarding', 
              shortDescription: 'Keep a constant pulse on employee engagement',
              headline: 'Gather real-time Data about how your people feel about work',
              description: 'Drive action and measure your impact with a continuous, real-time understanding of employee engagement.',
              icon: 'https://cdn-icons-png.flaticon.com/512/1160/1160358.png',
              backgroundImage: 'https://images.pexels.com/photos/255379/pexels-photo-255379.jpeg?cs=srgb&dl=pexels-miguel-%C3%A1-padri%C3%B1%C3%A1n-255379.jpg&fm=jpg',
              sliderImages: ['https://www.smartinsights.com/wp-content/uploads/2023/11/RACE-Digital-Marketing-Plan-Funnel-2023.png', 'https://blog.hootsuite.com/wp-content/uploads/2021/10/How-to-Create-a-Social-Media-Marketing-Strategy-in-9-Easy-Steps-Free-Template.png'],
              helperProcessId: 1,
              categories:'1',
            },
          ];
          this.totalRow = 1;
          this.apiDataLoading = false;
          this.apiNoResults = false;
          this.noResults = false;
        } else  {
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
                this.noResults = false;
                }
            })
            .finally(() => {
              this.loading = false;
            });
        }
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
</style>
