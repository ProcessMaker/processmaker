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
      <b-card
        v-for="process in processList"
        :key="process.id"
        img-src="/img/launchpad-images/process_background.svg"
        img-alt="Card Image"
        overlay
        class="card-process"
        @click="openProcessInfo(process)"
      >
        <b-card-text>
          <img
            class="icon-process"
            src="/img/default-process.svg"
            :alt="$t('Default Icon')"
          >
          <span class="title-process">{{ process.name }}</span>
        </b-card-text>
      </b-card>
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

export default {
  components: { pagination, CatalogueEmpty, SearchCards },
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
      ProcessMaker.apiClient
        .get(
          `processes?page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&category=${this.category.id}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&order_by=name&order_direction=asc",
        )
        .then((response) => {
          this.processList = response.data.data;
          this.totalRow = response.data.meta.total;
          this.totalPages = response.data.meta.total_pages;
        });
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
.card-process {
  width: 350px;
  height: 240px;
  margin-top: 1rem;
  margin-right: 1rem;
  border-radius: 16px;
}
.card-text {
  display: flex;
  flex-direction: column;
  align-items: baseline;
  padding-top: 15%;
}
.icon-process {
  font-size: 68px;
  margin-bottom: 1rem;
}
.title-process {
  color: #556271;
  font-family: Poppins, sans-serif;
  font-size: 20px;
  font-style: normal;
  font-weight: 700;
  line-height: normal;
  letter-spacing: -0.4px;
  text-transform: uppercase;
}
</style>
