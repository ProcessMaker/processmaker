<template>
  <div class="search" :class="{ 'show-on-mobile' : $root.mobileSearchVisible }">
    <b-input-group class="search-input-group">
      <b-input-group-prepend >
        <b-button
          :title="$t('Search Processes')"
          @click="fetch()"
        >
          <i class="fas fa-search search-icon" />
          <i class="fas fa-arrow-left search-icon" />
        </b-button>
      </b-input-group-prepend>

      <b-form-input
        class="search-box"
        v-model="filter"
        :placeholder="$t('Search categories and processes')"
        @keyup.enter="fetch()"
      />

      <b-input-group-append>
        <b-button
          v-b-tooltip.hover.bottom="$t('Clear Search')"
          @click="clearSearch()"
        >
          <i class="fas fa-times clear-icon" />
        </b-button>
      </b-input-group-append>
    </b-input-group>
    <div v-if="filteredCategories !== null"
         class="category-button-container">
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
      // Do not set the query if we are already on the route
      if (this.$route.query.categoryId !== category.id) {
        this.$router.push({name: "index", query: {categoryId: category.id}});
      }
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
@import '~styles/variables';

.search {
  margin-right: 15px;

  @media (max-width: $lp-breakpoint) {
    display: none;
    margin-right: 0;
    
    .input-group {
      width: 100%;
    }
  }
}

.show-on-mobile {
  display: block;
}

.search-input-group {
  margin-top: 10px;
  .btn-secondary, input {
    background-color: #E5EDF3;
    border-color: #E5EDF3;
    color: #5C5C63;
  }
    
  .fa-search {
    display: block;
  }

  .fa-arrow-left {
    display: none;
  }
  
  @media (max-width: $lp-breakpoint) {
    .input-group-prepend .btn {
      border-radius: 10px;
      border-top-right-radius: 0;
      border-bottom-right-radius: 0;
    }

    .input-group-append .btn {
      border-radius: 10px;
      border-top-left-radius: 0;
      border-bottom-left-radius: 0;
    }

    .fa-search {
      display:none;
    }

    .fa-arrow-left {
      display: block;
    }
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

.category-button-container {
  text-wrap: nowrap;
  overflow-x: scroll
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
