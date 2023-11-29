<template>
  <div>
    <breadcrumbs />
    <b-row>
      <b-col cols="2">
        <h4> {{ $t('Processes Browser') }} </h4>
        <MenuCatologue
          :data="listCategories"
          :select="selectCategorie"
          @wizardLinkSelect="showWizardTemplates = 'true'"
          class="mt-3"
        />
        <!-- <ul>
          <li>
            <button
              type="button"
              class="btn btn-link"
              @click="wizardTemplatesSelected"
            >
              {{ $t('Wizard Templates') }}
            </button>
          </li>
        </ul> -->
      </b-col>
      <b-col cols="10">
        <div
          class="d-flex justify-content-center py-5"
        >
          <wizard-templates v-if="showWizardTemplates" />
          <CatalogueEmpty v-if="!showWizardTemplates && !fields.length" />
        </div>
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
  },
};
</script>
