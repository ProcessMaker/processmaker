<template>
  <div>
    <b-input-group class="border-group">
      <b-input-group-prepend>
        <b-btn
          class="pr-1"
          variant="outline-secondary"
          :title="$t('Search here')"
          @click="fetch()"
        >
          <i class="fas fa-search search-icon" />
        </b-btn>
      </b-input-group-prepend>
      <b-form-input
        id="search-box"
        v-model="filter"
        class="pl-1"
        :placeholder="$t('Search here')"
        @keyup.enter="fetch()"
      />
      <b-input-group-append v-if="filter">
        <b-btn
          class="px-1"
          variant="outline-secondary"
          @click="clearSearch()"
        >
          <b-icon icon="x" />
        </b-btn>
      </b-input-group-append>
    </b-input-group>
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
    clearSearch() {
      this.filter = "";
      this.fetch();
    },
  },
};
</script>

<style scoped>
.btn-outline-secondary {
  font-size: 20px;
}
.btn-outline-secondary,
#search-box {
  border: 0px;
  background-color: white;
  padding: 0px, 16px, 0px, 16px;
  color: #6a7888;
  font-family: 'Open Sans', sans-serif;
  font-size: 16px;
  font-weight: 400;
  line-height: 22px;
  letter-spacing: -0.02em;
  text-align: left;
}
.border-group {
  border: 1px solid #cdddee;
  border-radius: 4px;
  margin-bottom: 16px;
}
</style>
