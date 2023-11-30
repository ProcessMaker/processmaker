<template>
  <div>
    <breadcrumbs />
    <b-row>
      <b-col cols="2">
        <h4> {{ $t('Processes Browser') }} </h4>
        <MenuCatologue
          :data="listCategories"
          :select="selectCategorie"
          class="mt-3"
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
          v-if="!showWizardTemplates && !fields.length"
          class="d-flex justify-content-center py-5"
        >
          <CatalogueEmpty />
        </div>
        <wizard-templates v-if="showWizardTemplates" />
      </b-col>
    </b-row>
  </div>
</template>

<script>
import MenuCatologue from "./menuCatologue.vue";
import CatalogueEmpty from "./CatalogueEmpty.vue";

import Breadcrumbs from "./Breadcrumbs.vue";
import WizardTemplates from "./WizardTemplates.vue";

export default {
  components: {
    MenuCatologue, CatalogueEmpty, Breadcrumbs, WizardTemplates,
  },
  data() {
    return {
      listCategories: [],
      fields: [],
      wizardTemplates: [],
      showWizardTemplates: false,
    };
  },
  mounted() {
    this.getCategories();
  },
  methods: {
    getCategories() {
      ProcessMaker.apiClient
        .get("process_categories")
        .then((response) => {
          this.listCategories = response.data.data;
        });
    },
    selectCategorie(value) {
      console.log(value);
    },
    wizardTemplatesSelected() {
      this.showWizardTemplates = true;
    },
  },
};
</script>
