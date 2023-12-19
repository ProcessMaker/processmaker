<template>
  <div>
    <SearchCards
      v-if="processList.length > 0"
      :filter-pmql="onFilter"
    />
    <div
      v-if="processList.length > 0"
      class="container processList"
    >
      <Card
        v-for="(process, index) in processList"
        :key="index"
        :process="process"
        @openProcessInfo="openProcessInfo"
      />
    </div>
    <pagination
      :total-row="totalRow"
      :total-pages="totalPages"
      @onPageChanged="onPageChanged"
    />

    <CatalogueEmpty v-if="processList.length === 0" />
  </div>
</template>

<script>
import CatalogueEmpty from "./CatalogueEmpty.vue";
import pagination from "./utils/pagination.vue";
import SearchCards from "./utils/SearchCards.vue";
import Card from "./utils/Card.vue";

export default {
  components: {
    pagination, CatalogueEmpty, SearchCards, Card,
  },
  props: ["category"],
  data() {
    return {
      processList: [],
      currentdata: [],
      currentPage: 1,
      limit: 7,
      totalRow: null,
      perPage: 9,
      order_by: "name",
      dir: "asc",
      data: null,
      totalPages: 1,
      pmql: "",
      bookmarkIcon: "far fa-bookmark",
    };
  },
  watch: {
    category() {
      this.pmql = "";
      this.loadCard();
    },
  },
  mounted() {
    this.loadCard();
  },
  methods: {
    loadCard() {
      const url = this.buildURL();
      ProcessMaker.apiClient
        .get(url)
        .then((response) => {
          this.processList = response.data.data;
          this.totalRow = response.data.meta.total;
          this.totalPages = response.data.meta.total_pages;
        });
    },
    /**
     * Build URL for Process Cards
     */
    buildURL() {
      if (this.category.id === 0) {
        return `process_bookmarks?page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&bookmark=true"
          + "&order_by=name&order_direction=asc";
      }
      return `processes?page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&category=${this.category.id}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&bookmark=true"
          + "&order_by=name&order_direction=asc";
    },
    /**
     * Go to process info
     */
    openProcessInfo(process) {
      this.$emit("openProcess", process);
    },
    /**
     * Load Cards in the new pagination
     */
    onPageChanged(page) {
      this.currentPage = page;
      this.loadCard();
    },
    /**
     * Build the PMQL
     */
    onFilter(value) {
      this.pmql = `(fulltext LIKE "%${value}%")`;
      this.loadCard();
    },
  },
};
</script>

<style>
.processList {
  display: flex;
  flex-wrap: wrap;
}
</style>
