<template>
  <div class="pagination">
    <b-button
      :disabled="currentPage === 1"
      class="pagination-button"
      variant="light"
      @click="firstPage"
    >
      <img src="/img/pagination-images/first.svg" :alt="$t('No Image')">
    </b-button>
    <b-button
      :disabled="currentPage === 1"
      class="pagination-button"
      variant="light"
      @click="previousPage"
    >
      <img src="/img/pagination-images/previous.svg" :alt="$t('No Image')">
    </b-button>

    <input
      ref="pageInput"
      v-model="pageInput"
      type="text"
      :placeholder="pageInputPlaceholder"
      class="pagination-input"
      @keyup.enter="redirectPage(pageInput)"
    >

    <span class="pagination-total">
      of {{ totalPageCount }}
    </span>

    <b-button
      :disabled="currentPage >= totalPageCount"
      class="pagination-button"
      variant="light"
      @click="nextPage"
    >
      <img src="/img/pagination-images/next.svg" :alt="$t('No Image')">
    </b-button>
    <b-button
      :disabled="currentPage >= totalPageCount"
      class="pagination-button"
      variant="light"
      @click="lastPage"
    >
      <img src="/img/pagination-images/last.svg" :alt="$t('No Image')">
    </b-button>
    <span class="pagination-total">
      {{ totalItems }}
    </span>
    <div class="btn-group dropup pagination-dropdown-group">
      <button
        type="button"
        class="btn dropdown-toggle pagination-dropup"
        data-toggle="dropdown"
        aria-haspopup="true"
        aria-expanded="false"
      >
        {{ perPageButton }}
      </button>
      <div class="dropdown-menu">
        <a
          v-for="(item, index) in itemsPerPage"
          :key="index"
          class="dropdown-item pagination-dropdown-items"
          href="#"
          @click="changePerPage(item.value)"
        >
          {{ item.perPage }}
        </a>
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
          perPage: this.$t("15 items"),
          value: 15,
        },
        {
          perPage: this.$t("30 items"),
          value: 30,
        },
        {
          perPage: this.$t("50 items"),
          value: 50,
        },
      ],
      pageInput: "",
      defaultPage: 1,
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
        return `${this.meta.total} item`;
      }
      return `${this.meta.total} items`;
    },
    perPageButton() {
      return `${this.meta.per_page} per Page`;
    },
    pageInputPlaceholder() {
      return `${this.currentPage}`;
    },
  },
  watch: {
    pageInputPlaceholder() {
      this.adjustInputWidth();
    },
  },
  mounted() {
    this.$nextTick(() => {
      this.adjustInputWidth();
    });
  },
  methods: {
    previousPage() {
      if (this.currentPage > 1) {
        this.goToPage(this.currentPage - 1);
      }
    },
    firstPage() {
      if (this.currentPage > 1) {
        this.goToPage(1);
      }
    },
    nextPage() {
      this.goToPage(this.currentPage + 1);
    },
    lastPage() {
      this.goToPage(this.totalPageCount);
    },
    goToPage(page) {
      this.$emit("page-change", page);
    },
    changePerPage(value) {
      this.$emit("per-page-change", value, this.defaultPage);
    },
    redirectPage(value) {
      this.$emit("page-change", value);
      this.pageInput = "";
    },
    adjustInputWidth() {
      const input = this.$refs.pageInput;
      const span = document.createElement("span");
      document.body.appendChild(span);

      span.style.font = window.getComputedStyle(input).font;
      span.style.position = "absolute";
      span.style.visibility = "hidden";
      span.style.whiteSpace = "nowrap";

      span.textContent = input.value || input.placeholder;

      const width = span.offsetWidth;
      document.body.removeChild(span);

      input.style.width = `${width + 30}px`;
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
  font-size: 16px;
  color: #556271;
  gap: 8px;
}
.pagination-button {
  display: flex;
  align-items: center;
  justify-content: left;
  border-radius: 4px;
  padding: 8px;
  width: 32px;
  height:32px;
}
.pagination-button:focus {
  box-shadow: 0px 0px 0px 3px rgba(72, 145, 255, 0.4);
}
.btn.disabled, .btn:disabled {
  opacity: 0.5;
}
.pagination-button:hover {
  background-color: #EBEEF2;
}
.btn.disabled:hover {
  background-color: transparent;
}
.pagination-current-page {
    color: #1572C2;
    font-weight: 700;
}
.pagination-total {
  margin-left: 10px;
  padding: 0px 8px 0px 8px;
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
.pagination-input {
  border: solid 1px;
  border-radius: 4px;
  width: 44px;
  height: 32;
  border-color: #CDDDEE;
}
.pagination-input::placeholder {
  text-align: center;
}
</style>
