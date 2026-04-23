<template>
  <div class="filter-bar justify-content-between">
    <form class="form-inline ">
      <div class="form-group ">
        <input
          v-model="filterText"
          type="text"
          class="form-control"
          :aria-label="$t('Search...')"
          :placeholder="$t('Search...')"
          @keyup="doFilter"
        >
        <i @click.prevent="doFilter" />
      </div>
    </form>
  </div>
</template>
<script>
export default {
  data() {
    return {
      filterText: "",
    };
  },
  methods: {
    doFilter() {
      if (this.filterText.length > 3) {
        this.$events.fire("filter-set", this.filterText);
      }
    },
  },
};
</script>
<style>

</style>
