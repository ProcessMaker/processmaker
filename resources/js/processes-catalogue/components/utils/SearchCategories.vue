<template>
  <div class="search-categories">
    <b-input-group>
      <b-input-group-prepend>
        <b-btn
          class="px-1"
          variant="outline-secondary"
          :title="$t('Search Categories')"
          @click="fetch()"
        >
          <i class="fas fa-search search-icon" />
        </b-btn>
      </b-input-group-prepend>
      <b-form-input
        id="search-box"
        v-model="filter"
        :placeholder="$t('Search Categories')"
        @keyup.enter="fetch()"
      />
      <b-input-group-append
        v-if="filter"
      >
        <b-btn
          class="px-1"
          variant="outline-secondary"
          @click="clearFilter()"
        >
          <b-icon icon="x" />
        </b-btn>
      </b-input-group-append>
    </b-input-group>
    <hr class="my-12">
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
  methods: {
    fetch() {
      this.filterPmql(this.filter);
    },
    clearFilter() {
      this.filter = "";
      this.fetch();
    },
    fillFilter(filter) {
      this.filter = filter;
    },
  },
};
</script>

<style scoped>
.btn-outline-secondary {
  font-size: 16px;
}
.btn-outline-secondary,
#search-box {
  border: none;
  background-color: #f7f9fb;
}
#search-box {
  color: #B9B9B9;
  padding: 4px;
  font-size: 16px;
  font-style: normal;
  font-weight: 400;
  line-height: 32.077px;
  letter-spacing: -0.38px;
}
.search-categories {
  padding-left: 1rem;
}
</style>
