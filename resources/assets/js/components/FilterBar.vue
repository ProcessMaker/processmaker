<template>
    <div class="filter-bar justify-content-between">
      <form class="form-inline ">
        <div class="form-group ">
          <label class="table-header"><strong>Completed Tasks<small class="font-weight-light">  (100)</small></strong></label>
          <input type="text" v-model="filterText" class="form-control" @keyup="doFilter" placeholder="search...">
          <i @click.prevent="doFilter"></i>
        </div>
      </form>
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
        if (this.filterText.length > 3) {
         this.$events.fire('filter-set', this.filterText)
        }
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
