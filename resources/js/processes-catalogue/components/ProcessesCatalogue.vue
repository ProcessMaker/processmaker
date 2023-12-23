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
          ref="category-list"
          title="Available Processes"
          preicon="fas fa-play-circle"
          class="mt-3"
          show-bookmark="true"
          :data="listCategories"
          :select="selectCategorie"
          @wizardLinkSelect="showWizardTemplates = 'true'"
          @addCategories="addCategories"
        />
      </b-col>
      <b-col cols="10">
        <div
          v-if="!showWizardTemplates && !showCardProcesses && !showProcess"
          class="d-flex justify-content-center py-5"
        >
          <CatalogueEmpty v-if="!showWizardTemplates && !fields.length" />
        </div>
        <div v-else>
          <CardProcess
            v-if="showCardProcesses && !showWizardTemplates"
            :category="category"
            @openProcess="openProcess"
          />
          <ProcessInfo
            v-if="showProcess && !showWizardTemplates"
            :process="selectedProcess"
            :current-user-id="currentUserId"
            :permission="permission"
            :is-documenter-installed="isDocumenterInstalled"
            @goBackCategory="returnedFromInfo"
          />
        <wizard-templates v-if="showWizardTemplates" />
      </div>
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
      listCategories: [{
        id: 0,
        name: "Bookmarked Processes",
        status: "ACTIVE",
      }],
      fields: [],
      wizardTemplates: [],
      showWizardTemplates: false,
      showCardProcesses: false,
      showProcess: false,
      category: null,
      selectedProcess: null,
      numCategories: 15,
      page: 1,
    };
  },
  mounted() {
    this.getCategories();
    this.checkSelectedProcess();
  },
  methods: {
    /**
     * Add new page of categories
     */
    addCategories() {
      this.page += 1;
      this.getCategories();
    },
    /**
     * Get list of categories
     */
    getCategories() {
      ProcessMaker.apiClient
        .get(`process_bookmarks/categories?status=active&page=${this.page}&per_page=${this.numCategories}`)
        .then((response) => {
          this.listCategories = [...this.listCategories, ...response.data.data];
        });
    },
    /**
     * Check if there is a pre-selected process
     */
    checkSelectedProcess() {
      if (this.process) {
        this.openProcess(this.process);
        const categories = this.process.process_category_id;
        const categoryId = typeof categories === "string" ? categories.split(",")[0] : categories;
        ProcessMaker.apiClient
          .get(`process_bookmarks/${categoryId}`)
          .then((response) => {
            this.category = response.data;
          });
      }
    },
    /**
     * Select a category and show display
     */
    selectCategorie(value) {
      this.category = value;
      this.selectedProcess = null;
      this.showCardProcesses = true;
      this.showWizardTemplates = false;
      this.showProcess = false;
    },
    /**
     * Select a wizard templates and show display
     */
    wizardTemplatesSelected() {
      this.showWizardTemplates = true;
      this.showCardProcesses = false;
      this.showProcess = false;
    },
    /**
     * Select a process and show display
     */
    openProcess(process) {
      this.showCardProcesses = false;
      this.showProcess = true;
      this.selectedProcess = process;
    },
    /**
     * Return a process cards from process info
     */
    returnedFromInfo() {
      this.selectCategorie(this.category);
    },
  },
};
</script>
