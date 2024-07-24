<template>
  <div class="h-100">
    <SearchCards
      v-if="processList.length > 0 || showEmpty"
      :filter-pmql="onFilter"
    />
    <div
      id="infinite-list-card"
      v-show="processList.length > 0"
      class="processList d-flex"
      ref="processListContainer"
    >
      
      <template v-for="(process, index) in processList">
        <Card
          :key="`${index}_${renderKey}`"
          :process="process"
          :show-cards="true"
          :current-page="currentPage"
          :total-pages="totalPages"
          :card-message="cardMessage"
          :loading="false"
          @openProcessInfo="openProcessInfo"
          @callLoadCard="loadCard"
          :hideBookmark="categoryId === 'all_templates'"
        />

        <div v-if="(index % perPage === perPage - 1) && processList.length >= perPage" :class="separatorClass">
          <Card
            v-if="((index + 1) === processList.length) && ((index + 1) < totalPages * perPage)"
            :show-cards="false"
            :current-page="counterPage + Math.floor(index / perPage)"
            :total-pages="totalPages"
            :card-message="'show-more'"
            :loading="loading"
            @callLoadCard="loadCard"
          />

          <div v-else-if="((index + 1) === processList.length) && currentPage === totalPages && ((index + 1) === processList.length)">
          </div>
          <Card v-else
          :show-cards="false"
            :current-page="counterPage + Math.floor(index / perPage)"
            :total-pages="totalPages"
            :card-message="cardMessage"
            :loading="loading"
            @callLoadCard="loadCard"
            />
        </div>
      </template>
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
import { EventBus } from '../index.js';


export default {
  components: {
    pagination, CatalogueEmpty, SearchCards, Card,
  },
  mixins: [dataLoadingMixin],
  props: ["categoryId"],
  data() {
    return {
      counterPage: 2,
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
      filter: "",
      showEmpty: false,
      loading: false,
      renderKey: 0,
      showMoreVisible: false,
      cardMessage: "show-more",
      sumHeight: 0,
      isCardProcess: true,
      sizeChange: false,
    };
  },
  computed: {
    separatorClass() {
      const classes = {
        'separator-class': true,
        'width-changed': this.sizeChange,
      };
      return classes;
    },
  },
  watch: {
    async categoryId() {
      const listCard = document.querySelector(".processes-info");
      listCard.scrollTop = 0;
      this.pmql = "";
      this.filter = "";
      this.currentPage = 1;
      this.processList = [];

      await this.$nextTick();
      this.loadCard();
    },
  },
  mounted() {
    this.loadCard(()=>{
      this.$nextTick(()=>{
          const listCard = document.querySelector(".processes-info");
          listCard.addEventListener("scrollend", () => this.handleScroll());
      });
    }, null);
    this.$root.$on("sizeChanged", (val) => {
      this.handleSizeChange(val);
    });
  },
  beforeDestroy() {
    this.isCardProcess = false;
    const listCard = document.querySelector(".processes-info");
    listCard?.removeEventListener("scrollend", this.handleScroll);
  },
  methods: {
    handleSizeChange(value) {
      this.sizeChange = value;
    },
    loadCard(callback, message) {
      if(message === 'bookmark') {
        this.processList = [];
        this.page = 1;
      }
      this.loading = true;
      const url = this.buildURL();
      ProcessMaker.apiClient
        .get(url)
        .then((response) => {
          this.loading = false;
          this.processList = this.processList.concat(response.data.data);
          this.totalRow = response.data.meta.total;
          this.totalPages = response.data.meta.total_pages;
          this.showMoreVisible = this.processList.length < this.totalRow;
          this.renderKey = this.renderKey + 1;
          callback?.();
          const container =  document.querySelector(".processes-info");
          if(!callback) {
            this.$nextTick(() => {
                if((container.scrollTop+container.clientHeight)>=container.scrollHeight-5){
                  container.scrollTop = container.scrollTop - 1050;
                } 
            });
          } 
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
      if (this.categoryId === 'all_processes') {
        return "process_bookmarks/processes?"
          + `&page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&bookmark=true"
          + "&launchpad=true"
          + "&cat_status=ACTIVE"
          + "&order_by=name&order_direction=asc";
      }
      if (this.categoryId === 'bookmarks') {
        return `process_bookmarks?page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&bookmark=true"
          + "&launchpad=true"
          + "&order_by=name&order_direction=asc";
      }
      if (this.categoryId === 'all_templates') {
        return `templates/process?page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&filter=${encodeURIComponent(this.filter)}`
          + `&order_by=name`
          + `&order_direction=asc`
          + `&include=user,categories,category`;
      }
      return `process_bookmarks/processes?page=${this.currentPage}`
          + `&per_page=${this.perPage}`
          + `&category=${this.categoryId}`
          + `&pmql=${encodeURIComponent(this.pmql)}`
          + "&bookmark=true"
          + "&launchpad=true"
          + "&order_by=name&order_direction=asc";
    },
    /**
     * Go to process info
     */
    openProcessInfo(process) {
      if (this.categoryId === 'all_templates') {
        EventBus.$emit('templates-selected', { template: process, type: "Process" });
        return;
      }
      this.$router.push({ name: "show", params: { process: process, processId: process.id } });
      this.$emit("openProcess", process);
    },
    /**
     * Load Cards in the new pagination
     */
    onPageChanged(page) {
      if (page > this.currentPage && this.processList.length < this.totalRow) {
        this.currentPage = page;
        this.loadCard();
      }
    },
    /**
     * Build the PMQL
     */
    onFilter(value, showEmpty = false) {
      this.processList = [];
      this.currentPage = 1;
      if (value) {
        this.pmql = `(fulltext LIKE "%${value}%")`;
      } else {
        this.pmql = "";
      }
      this.filter = value;
      this.showEmpty = showEmpty;
      this.loadCard();
    },
    handleScroll() {
      const container =  document.querySelector(".processes-info");
      if ((container.scrollTop + container.clientHeight >= container.scrollHeight - 5) && this.isCardProcess) {
        this.cardMessage = "show-page";
        this.onPageChanged(this.currentPage + 1);
      }
    },
  },
};
</script>

<style scoped lang="scss">

@import '~styles/variables';
.processList {
  display: flex;
  flex-wrap: wrap;
  position: relative;
  overflow: unset;
  justify-content: flex-start;

  @media (max-width: $lp-breakpoint) {
    display: block;
    height: auto;
  }
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
.separator-class {
  @media (max-width: $lp-breakpoint) {
    display: block;
    height: auto;
    width: 95%;
  }

  @media (min-width: 641px) and (max-width:1075px) {
    width: 343px;
  }

  @media (min-width: 1076px) and (max-width:1520px) {
    width: 772px;
  }

  @media (min-width: 1521px) and (max-width: 1789px) {
    width: 1160px;
  }

  @media (min-width: 1790px) and (max-width: 1879px) {
    width: 1156px;
  }

  @media (min-width: 1880px) and (max-width: 2148px){
    width: 1544px;
  }

  @media (min-width: 2149px) and (max-width: 2438px){
    width: 1926px;
  }

  @media (min-width: 2439px){
    width: 2308px;
  }
}
.separator-class.width-changed {
  @media (min-width: 641px) and (max-width:767px) {
    width: 340px;
  }

  @media (min-width: 768px) and (max-width:1075px) {
    width: 678px;
  }

  @media (min-width: 1076px) and (max-width:1520px) {
    width: 1156px;
  }

  @media (min-width: 1521px) and (max-width: 1789px) {
    width: 1156px;
  }

  @media (min-width: 1790px) and (max-width: 1879px) {
    width: 1542px;
  }

  @media (min-width: 1880px) and (max-width: 2148px){
    width: 1859px;
  }

  @media (min-width: 2149px) and (max-width: 2438px){
    width: 1926px;
  }

  @media (min-width: 2439px){
    width: 2308px;
  }
}
</style>
