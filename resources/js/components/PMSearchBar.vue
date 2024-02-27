<template>
  <div class="d-flex align-items-start mb-3">
    <div>
      <slot name="left-content"></slot>
    </div>
    <div class="search-bar flex-grow w-100">
      <div class="d-flex align-items-center">
        <i class="fa fa-search ml-3"></i>
        <textarea type="text"
                  :aria-label="''"
                  :placeholder="$t('Search here')"
                  rows="1"
                  @keydown.enter.prevent
                  class="pmql-input">
        </textarea>
        <i class="fa fa-times pl-1 pr-3"
           role="button"></i>
      </div>
    </div>
    <div>
      <slot name="right-content"></slot>
    </div>
  </div>
</template>

<script>
  export default {
    components: {
    },
    props: {
    },
    data() {
      return {
      };
    },
    methods: {
    }
  }
</script>

<style>
</style>
<style scoped>
</style>