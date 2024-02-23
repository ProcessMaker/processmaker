<template>
  <div>
    <SearchCards
      v-if="processList.length > 0 || showEmpty"
      :filter-pmql="onFilter"
    />
    <div
      v-show="!loading && processList.length > 0"
      class="processList"
    >
      <Card
        v-for="(process, index) in processList"
        :key="index"
        :process="process"
        @openProcessInfo="openProcessInfo"
      />
      <pagination
        :total-row="totalRow"
        :total-pages="totalPages"
        @onPageChanged="onPageChanged"
      />
    </div>
    <div
      v-if="loading"
      class="d-flex justify-content-center align-items-center m-5"
    >
      <data-loading
        v-show="shouldShowLoader"
        empty-icon="beach"
      />
    </div>
    <CatalogueEmpty
      v-if="!loading && processList.length === 0"
      :show-empty="showEmpty"
      @wizardLinkSelect="wizardLinkSelected"
    />
  </div>
</template>

<script>
import CatalogueEmpty from "./CatalogueEmpty.vue";
import pagination from "./utils/pagination.vue";
import SearchCards from "./utils/SearchCards.vue";
import dataLoadingMixin from "../../components/common/mixins/apiDataLoading";
import Card from "./utils/Card.vue";

export default {
  components: {
    pagination, CatalogueEmpty, SearchCards, Card,
  },
  mixins: [dataLoadingMixin],
  props: ["category"],
  data() {
    return {
      processList: [],
      currentdata: [],
      currentPage: 1,
      limit: 7,
      totalRow: null,
      perPage: 12,
      order_by: "name",
      dir: "asc",
      data: null,
      totalPages: 1,
      pmql: "",
      bookmarkIcon: "far fa-bookmark",
      showEmpty: false,
      loading: false,
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
      this.loading = true;
      const url = this.buildURL();
      ProcessMaker.apiClient
        .get(url)
        .then((response) => {
          this.loading = false;
          this.processList = response.data.data;
          this.totalRow = response.data.meta.total;
          this.totalPages = response.data.meta.total_pages;
        });
    },
    /**
     * Go to wizard templates section
     */
    wizardLinkSelected() {
      this.$emit("wizardLinkSelect");
    },
    /**
     * Build URL for Process Cards
     */
    buildURL() {
      if (this.category === undefined || this.category.id === -1) {
        return "process_bookmarks/processes?"
          + `&page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&bookmark=true"
          + "&cat_status=ACTIVE"
          + "&order_by=name&order_direction=asc";
      }
      if (this.category.id === 0) {
        return `process_bookmarks?page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&bookmark=true"
          + "&order_by=name&order_direction=asc";
      }
      return `process_bookmarks/processes?page=${this.currentPage}`
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
    onFilter(value, showEmpty = false) {
      this.pmql = `(fulltext LIKE "%${value}%")`;
      this.showEmpty = showEmpty;
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
.text-custom {
  color: #1572C2;
  width: 200px;
  height: 200px;
  border: 1.2em solid currentcolor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: 0.75s linear infinite spinner-border;
}
</style>
