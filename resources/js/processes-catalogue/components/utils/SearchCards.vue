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
    <div v-if="$root.filteredCategories !== null"
         class="category-button-container">
      <template v-if="$root.filteredCategories.length > 0">
        <b-button v-for="category in $root.filteredCategories"
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
    };
  },
  mounted() {
    this.$root.filteredCategories = null;
  },
  methods: {
    fetch() {
      if (this.filter === "") {
        this.clearSearch();
        return;
      }
      this.filterPmql(this.filter, true);
      this.filterCategories();
    },
    clearSearch() {
      this.filter = null;
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
      this.$root.$emit("filter-categories", this.filter);
    },
  },
};
</script>

<style scoped lang="scss">
@import '~styles/variables';

.search {
  margin-right: 15px;
  margin-left: 15px;
  min-width: 300px;
  @media (max-width: $lp-breakpoint) {
    display: none;
    margin-right: 0;
    
    .input-group {
      width: 100%;
    }
  }
}

@media (min-width: 641px) and (max-width: 1075px) {
  .search {
    width: 300px;
  }
}

@media (min-width: 1270px) and (max-width: 1520px) {
  .search {
    width: 701px;
  }
}

@media (min-width: 1521px) and (max-width: 1789px) {
  .search {
    width: 1059px;
  }
}

@media (min-width: 1790px) and (max-width: 2148px){
  .search {
    width: 1059px;
  }
}

@media (min-width: 2149px) and (max-width: 2507px){
  .search {
    width: 1775px;
  }
}

@media (min-width: 2508px){
  .search {
    width: 100%;
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
