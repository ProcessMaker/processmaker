<template>
  <div class="search-group">
    <b-btn
      class="btn btn-input"
      :title="$t('Search Processes')"
      @click="fetch()"
    >
      <i class="fas fa-search search-icon" />
    </b-btn>
    <b-form-input
      id="search-box"
      v-model="filter"
      :placeholder="$t('Search Processes')"
      @keyup.enter="fetch()"
    />
    <div v-if="filter">
      <b-btn
        class="px-1 btn btn-input"
        v-b-tooltip.hover.bottom="$t('Clear Search')"
        @click="clearSearch()"
      >
        <b-icon class="icon-close" icon="x" />
      </b-btn>
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
  methods: {
    fetch() {
      this.filterPmql(this.filter, true);
    },
    clearSearch() {
      this.filter = "";
      this.fetch();
    },
  },
};
</script>

<style scoped>
.btn-input {
  font-size: 20px;
}
.search-group {
  display: flex;
  align-items: center;
}
.btn-input,
#search-box {
  border: none;
  background-color: #f7f9fb;
}
#search-box {
  font-size: 19px;
  font-style: normal;
  font-weight: 400;
  line-height: 32.077px;
  letter-spacing: -0.38px;
}
.form-control:focus {
  border: none !important;
  box-shadow: none;
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
.icon-close {
  width: 24px;
  height: 24px;
}
</style>
