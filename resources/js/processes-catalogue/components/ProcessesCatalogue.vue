<template>
  <div>
    <breadcrumbs
      ref="breadcrumb"
      :category="category ? category.id : ''"
    />
    <b-row>
      <b-col cols="2">
        <h4> {{ $t('Processes Browser') }} </h4>
        <MenuCatologue
          :data="listCategories"
          :select="selectCategorie"
          title="Avaible Processses"
          preicon="fas fa-play-circle"
          class="mt-3"
          @addCategories="addCategories"
        />
        <ul>
          <li>
            <button
              type="button"
              class="btn btn-link"
              @click="wizardTemplatesSelected"
            >
              {{ $t('Wizard Templates') }}
            </button>
          </li>
        </ul>
      </b-col>
      <b-col cols="10">
        <div
          v-if="!showWizardTemplates && !showCardProcesses"
          class="d-flex justify-content-center py-5"
        >
          <CatalogueEmpty />
        </div>
        <wizard-templates v-if="showWizardTemplates" />
        <CardProcess
          v-if="showCardProcesses"
          :category="category"
        />
      </b-col>
    </b-row>
  </div>
</template>

<script>
import MenuCatologue from "./menuCatologue.vue";
import CatalogueEmpty from "./CatalogueEmpty.vue";
import CardProcess from "./CardProcess.vue";
import Breadcrumbs from "./Breadcrumbs.vue";
import WizardTemplates from "./WizardTemplates.vue";

export default {
  components: {
    MenuCatologue, CatalogueEmpty, Breadcrumbs, CardProcess, WizardTemplates,
  },
  data() {
    return {
      listCategories: [],
      fields: [],
      wizardTemplates: [],
      showWizardTemplates: false,
      showCardProcesses: false,
      category: null,
      numCategories: 15,
      page: 1,
    };
  },
  mounted() {
    this.getCategories();
  },
  methods: {
    addCategories() {
      this.page += 1;
      this.getCategories();
    },
    getCategories() {
      ProcessMaker.apiClient
        .get(`process_categories?page=${this.page}&per_page=${this.numCategories}`)
        .then((response) => {
          this.listCategories = [...this.listCategories, ...response.data.data];
        });
    },
    selectCategorie(value) {
      this.category = value;
      this.$refs.breadcrumb.getCategory(value.name);
      this.showCardProcesses = true;
      this.showWizardTemplates = false;
    },
    wizardTemplatesSelected() {
      this.showWizardTemplates = true;
      this.showCardProcesses = false;
    },
  },
};
</script>
