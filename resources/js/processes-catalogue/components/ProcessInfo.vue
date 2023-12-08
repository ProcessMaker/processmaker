<template>
  <div>
    <breadcrumbs
      ref="breadcrumb"
      :category="selectCategory"
      :process="process.name"
    />
    <b-row>
      <b-col cols="2">
        <h4>{{ $t("Processes Browser") }}</h4>
        <MenuCatologue
          :data="listCategories"
          :select="selectCategorie"
          class="mt-3"
        />
      </b-col>
      <b-col cols="10">
        <div class="d-flex">
          <b-col cols="9">
            <process-map
              :process="process"
              :permission="permission"
              :current-user-id="currentUserId"
              :is-documenter-installed="isDocumenterInstalled"
            />
            <processes-carousel
              :process="process"
            />
          </b-col>
          <b-col cols="3">
            <process-options :process="process" />
          </b-col>
        </div>
        <b-col cols="12">
          Process Tab
        </b-col>
      </b-col>
    </b-row>
  </div>
</template>

<script>
import MenuCatologue from "./menuCatologue.vue";
import ProcessesCarousel from "./ProcessesCarousel.vue";
import ProcessMap from "./ProcessMap.vue";
import ProcessOptions from "./ProcessOptions.vue";
import Breadcrumbs from "./Breadcrumbs.vue";

export default {
  components: {
    ProcessOptions,
    Breadcrumbs,
    ProcessMap,
    MenuCatologue,
    ProcessesCarousel,
  },
  props: ["process", "permission", "isDocumenterInstalled", "currentUserId", "category"],
  data() {
    return {
      listCategories: [],
      selectCategory: 0,
    };
  },
  created() {
    this.selectCategory = this.selectedCategory();
    this.getCategories();
  },
  methods: {
    selectedCategory() {
      if (this.category) {
        return this.category;
      }
      const categories = this.process.process_category_id;
      return typeof categories === "string" ? categories.split(",")[0] : categories;
    },
    getCategories() {
      ProcessMaker.apiClient
        .get("process_categories")
        .then((response) => {
          this.listCategories = response.data.data;
        });
    },
    selectCategorie(value) {
      // TODO Flow from processInfo to ProcessesCtalogue
    },
  },
};
</script>
