<template>
  <div class="container">
    <b-input-group>
      <b-input-group-prepend>
        <b-btn
          class="btn-search-run px-2"
          variant="custom"
          :title="$t('Search Processes')"
          @click="fetch()"
        >
          <i class="fas fa-search search-icon" />
        </b-btn>
      </b-input-group-prepend>
      <b-form-input
        id="search-box"
        v-model="filter"
        class="ml-1"
        :placeholder="$t('Search Processes')"
      />
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
  },
};
</script>

<style scoped>
.btn-custom {
  background-color: #f7f9fb;
}
</style>
