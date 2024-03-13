<template>
  <div class="pagination">
    <button
      :disabled="currentPage === 1"
      class="pagination-button"
      @click="previousPage"
    >
      <strong><</strong>
    </button>

    <div class="pagination-button">
      <span class="pagination-current-page">{{ currentPage }}</span>
      -
      <span>{{ totalPageCount }}</span>
    </div>

    <button
      :disabled="currentPage >= totalPageCount"
      class="pagination-button"
      @click="nextPage"
    >
      <strong>></strong>
    </button>
    <span class="pagination-total">
      {{ totalItems }}
    </span>
    <div class="btn-group dropup pagination-dropdown-group">
      <button type="button" class="btn dropdown-toggle pagination-dropup" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        {{ perPageButton }}
      </button>
      <div class="dropdown-menu">
        <a v-for="(item, index) in itemsPerPage" :key="index" class="dropdown-item pagination-dropdown-items" href="#">{{ item.perPage }}</a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    meta: {
      type: Object,
      default() {
        return {
          filter: "",
          sort_by: "id",
          sort_order: "DESC",
          count: 0,
          total_pages: 0,
          current_page: 1,
          from: 10,
          last_page: 1,
          links: [],
          path: "/",
          per_page: 15,
          to: 0,
          total: 0,
        };
      },
    },
  },
  data() {
    return {
      itemsPerPage: [
        {
          perPage: "15 items",
        },
        {
          perPage: "30 items",
        },
        {
          perPage: "50 items",
        },
      ],
    };
  },
  computed: {
    currentPage() {
      return this.meta.current_page;
    },
    totalPageCount() {
      return this.meta.total_pages;
    },
    totalItems() {
      if (this.meta.total === 1) {
        return this.meta.total + " item";
      }
      return this.meta.total + " items";
    },
    perPageButton() {
      return this.meta.per_page + "per Page"
    },
  },
  mounted() {
    console.log(this.meta);
  },
  methods: {
    previousPage() {
      if (this.currentPage > 1) {
        this.goToPage(this.currentPage - 1);
      }
    },
    nextPage() {
      if (this.currentPage < this.totalPageCount) {
        this.goToPage(this.currentPage + 1);
      }
    },
    goToPage(page) {
      this.$emit('page-change', page);
    },
  },
};
</script>

<style>
.pagination {
  display: flex;
  justify-content: left;
  align-items: center;
  margin-top: 20px;
  font-weight: 400;
  font-size: 15px;
  color: #5C5C63;
}
.pagination-button {
  background-color: #FFFFFF;
  border: 1px solid #CDDDEE;
  border-radius: 8px;
  padding: 5px 10px;
  color: #5C5C63;
  margin-right: 8px;
  font-weight: 400;
  font-size: 15px;
}
.pagination-button:hover {
  background-color: #FAFBFC;
}
.pagination-current-page {
    color: #1572C2;
    font-weight: 700;
}
.pagination-total {
  margin-left: 10px;
}
.pagination-dropup {
  font-weight: 400;
  font-size: 15px;
  color: #5C5C63;
  text-transform: none;
}
.pagination-dropdown-group {
  min-width: 5rem;
}
.pagination-dropdown-items {
  font-weight: 400;
  font-size: 14.5px;
  color: #5C5C63;
}
</style>
