<template>
    <div class="filter-bar justify-content-between">
      <form class="form-inline ">
        <div class="form-group ">
          <input type="text" v-model="filterText" class="form-control" @keyup="doFilter" placeholder="Search...">
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
      }
    }
  }
</script>
<style>

</style>
