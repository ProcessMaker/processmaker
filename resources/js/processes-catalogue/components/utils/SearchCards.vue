<template>
  <div>
    <b-input-group>
      <b-input-group-prepend>
        <b-btn
          variant="outline-secondary"
          :title="$t('Search Processes')"
          @click="fetch()"
        >
          <i class="fas fa-search search-icon" />
        </b-btn>
      </b-input-group-prepend>
      <b-form-input
        id="search-box"
        v-model="filter"
        :placeholder="$t('Search Processes')"
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
.btn-outline-secondary {
  font-size: 20px;
}
.btn-outline-secondary,
#search-box {
  border: none;
  background-color: #f7f9fb;
}
#search-box {
  color: #B9B9B9;
  font-size: 19px;
  font-style: normal;
  font-weight: 400;
  line-height: 32.077px;
  letter-spacing: -0.38px;
}
</style>
