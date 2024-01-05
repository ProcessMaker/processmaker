<template>
  <!-- <div class="container"> -->
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
          class="ml-1 d-flex align-items-center"
          :placeholder="$t('Search Processes')"
          @keyup.enter="fetch()"
        />
        <!-- <button class="btn-clear-search" @click="clearSearch()">
          <i class="fas fa-times"></i>
        </button> -->
        <!-- TO DO: Apply below changes when Styles will be approved (and remove commented code) -->
        <b-input-group-append>
              <b-button variant="outline-dark" @click="clearSearch">X</b-button>
            </b-input-group-append>
        </b-input-group>
  <!-- </div> -->
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
.search-container {
  position: relative;
  width: 95%;
}

.btn-custom {
  background-color: #f7f9fb;
}

.btn-clear-search {
  position: absolute;
  top: 50%;
  right: 8px;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  font-size: 16px;
  z-index: 1;
}
</style>
