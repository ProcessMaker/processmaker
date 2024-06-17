<template>
  <div class="search">
    <b-input-group class="search-input-group">
      <b-input-group-prepend >
        <b-button
          :title="$t('Search Processes')"
          @click="fetch()"
        >
          <i class="fas fa-search search-icon" />
        </b-button>
      </b-input-group-prepend>

      <b-form-input
        class="search-box"
        v-model="filter"
        :placeholder="$t('Search Processes')"
        @keyup.enter="fetch()"
      />

      <b-input-group-append>
        <b-button
          v-if="filter"
          v-b-tooltip.hover.bottom="$t('Clear Search')"
          @click="clearSearch()"
        >
          <b-icon class="icon-close" icon="x" />
        </b-button>
      </b-input-group-append>
    </b-input-group>
    <div v-if="filteredCategories !== null">
      <template v-if="filteredCategories.length > 0">
        <b-button v-for="category in filteredCategories"
          :key="category.id"
          class="category-button"
          variant="light"
          @click="selectCategory(category)"
        >
        {{ category.name }}
        </b-button>
      </template>
      <template v-else>
        <div class="category-no-result">
          {{ $t('No matching categories were found') }}
        </div>
      </template>
    </div>
  </div>
</template>

<script>
export default {
  props: ["filterPmql"],
  data() {
    return {
      filter: "",
      filteredCategories: null,
    };
  },
  methods: {
    fetch() {
      this.filterPmql(this.filter, true);
      this.filterCategories();
    },
    clearSearch() {
      this.filter = "";
      this.filterCategories();
      this.fetch();
    },
    selectCategory(category) {
      this.$router.push({ name: "index", query: { categoryId: category.id } });
      this.clearSearch();
    },
    filterCategories() {
      if (!this.filter) {
        this.filteredCategories = null;
        return;
      }
      this.filteredCategories = this.$root.categories.filter((category) => {
        return category.name
          .toLowerCase()
          .includes(this.filter.toLowerCase());
      });
    },
  },
};
</script>

<style scoped lang="scss">
.search-input-group {
  margin-top: 10px;
  .btn-secondary, input {
    background-color: #E5EDF3;
    border-color: #E5EDF3;
    color: #5C5C63;
  }
}

.category-button {
  color: #50606D;
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  border-radius: 6px;
  border: 1px solid #CDDDEE;
  text-transform: inherit;
  
  margin-top: 10px;
  margin-right: 5px;
}

.category-no-result {
  color: #50606D;
  font-size: 14px;
  font-style: normal;
  font-weight: 400;
  border-radius: 6px;
  border: 1px solid #CDDDEE;

  text-align: center;
  padding: 10px 0;
  background: none;
  margin-top: 10px;
}
</style>
