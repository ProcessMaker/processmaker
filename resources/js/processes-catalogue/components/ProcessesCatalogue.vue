<template>
  <div>
    <breadcrumbs
      ref="breadcrumb"
      :category="category ? category.name : ''"
      :process="selectedProcess ? selectedProcess.name : ''"
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
          v-if="!showWizardTemplates && !showCardProcesses && !showProcess"
          class="d-flex justify-content-center py-5"
        >
          <CatalogueEmpty />
        </div>
        <wizard-templates v-if="showWizardTemplates" />
        <CardProcess
          v-if="showCardProcesses"
          :category="category"
          @openProcess="openProcess"
        />
        <ProcessInfo
          v-if="showProcess"
          :process="selectedProcess"
          :current-user-id="currentUserId"
          :permission="permission"
          :is-documenter-installed="isDocumenterInstalled"
          @goBackCategory="returnedFromInfo"
        />
      </b-col>
    </b-row>
  </div>
</template>

<script>
import ProcessInfo from "./ProcessInfo.vue";
import MenuCatologue from "./menuCatologue.vue";
import CatalogueEmpty from "./CatalogueEmpty.vue";
import CardProcess from "./CardProcess.vue";
import Breadcrumbs from "./Breadcrumbs.vue";
import WizardTemplates from "./WizardTemplates.vue";

export default {
  components: {
    MenuCatologue, CatalogueEmpty, Breadcrumbs, CardProcess, WizardTemplates, ProcessInfo,
  },
  props: ["permission", "isDocumenterInstalled", "currentUserId", "process"],
  data() {
    return {
      listCategories: [],
      fields: [],
      wizardTemplates: [],
      showWizardTemplates: false,
      showCardProcesses: false,
      showProcess: false,
      category: null,
      selectedProcess:null,
      numCategories: 15,
      page: 1,
    };
  },
  mounted() {
    this.getCategories();
    this.checkSelectedProcess();
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
    checkSelectedProcess() {
      if (this.process) {
        this.openProcess(this.process);
        const categories = this.process.process_category_id;
        const categoryId = typeof categories === "string" ? categories.split(",")[0] : categories;
        ProcessMaker.apiClient
          .get(`process_categories/${categoryId}`)
          .then((response) => {
            this.category = response.data;
          });
      }
    },
    selectCategorie(value) {
      this.category = value;
      this.selectedProcess = null;
      this.showCardProcesses = true;
      this.showWizardTemplates = false;
      this.showProcess = false;
    },
    wizardTemplatesSelected() {
      this.showWizardTemplates = true;
      this.showCardProcesses = false;
      this.showProcess = false;
    },
    openProcess(process) {
      this.showCardProcesses = false;
      this.showProcess = true;
      this.selectedProcess = process;
    },
    returnedFromInfo() {
      this.selectCategorie(this.category);
    },
  },
};
</script>
