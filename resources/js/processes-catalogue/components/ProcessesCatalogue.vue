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
      </b-col>
      <b-col cols="10">
        <div
          v-if="!fields.length"
          class="d-flex justify-content-center py-5"
        >
          <CatalogueEmpty />
        </div>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import MenuCatologue from "./menuCatologue.vue";
import CatalogueEmpty from "./CatalogueEmpty.vue";

import Breadcrumbs from "./Breadcrumbs.vue";

export default {
  components: { MenuCatologue, CatalogueEmpty, Breadcrumbs },
  data() {
    return {
      listCategories: [],
      fields: [],
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
