<template>
  <div class="input-box">
    <div class="search-categories">
      <b-btn
        class="px-1"
        variant="btn btn-input"
        :title="$t('Search Categories')"
        @click="fetch()"
      >
        <i class="fas fa-search search-icon" />
      </b-btn>
      <b-form-input
        id="search-box"
        v-model="filter"
        :placeholder="$t('Search Categories')"
        @keyup.enter="fetch()"
      />
      <div
        v-if="filter"
      >
        <button
          class="btn btn-input btn-clear"
          v-b-tooltip.hover.bottom="$t('Clear Search')"
          @click="clearFilter()"
        >
          <b-icon class="icon-close" icon="x" />
        </button>
      </div>
    </div>
    <hr class="my-1">
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
.btn-input,
#search-box {
  border: none;
  background-color: #f7f9fb;
}
#search-box {
  padding: 4px;
  font-size: 16px;
  font-style: normal;
  font-weight: 400;
  line-height: 32.077px;
  letter-spacing: -0.38px;
}
.form-control:focus {
  border: none !important;
  box-shadow: none;
}
.search-categories {
  display: flex;
  align-items: center;
}
.input-box {
  padding-left: 1rem;
  margin-bottom: 15px;
}
.btn-input {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 0px;
  font-size: 16px;
  color: #6a7888;
  border-radius: 4px;
  width: 24px;
  height: 24px;
}
.btn-clear:hover {
  color: #1572c2;
  background-color: #e5edf3;
}
.icon-close {
  width: 24px;
  height: 24px;
}
</style>
