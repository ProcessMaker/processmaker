<template>
    <div class="filter-bar">
      <div>
        <form class="form-inline justify-content-between">
            <label class="table-header"><strong>Completed Tasks<small class="font-weight-light">  (100)</small></strong></label>
            <input type="text" v-model="filterText" class="form-control" @keyup.enter="doFilter" placeholder="search...">
        </form>
      </div>
    </div>
</template>

<script>
  export default {
    data () {
      return {
        filterText: ''
      }
    },
    methods: {
      doFilter () {
        this.$events.fire('filter-set', this.filterText)
      },
      resetFilter () {
        this.filterText = ''
        this.$events.fire('filter-reset')
      }
    }
  }
</script>
<style>
.filter-bar {
  padding: 10px;
}
</style>
