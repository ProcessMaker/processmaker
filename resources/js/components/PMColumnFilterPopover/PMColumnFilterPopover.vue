<template>
  <div :id="id">
    <b-button :id="'pm-cff-button-'+id" 
              variant="light"
              size="sm">
      <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-three-dots-vertical" viewBox="0 0 16 16">
        <path d="M9.5 13a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0m0-5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0"/>
      </svg>
    </b-button>
    <b-popover :target="'pm-cff-button-'+id"
               triggers="click"
               :show.sync="popoverShow"
               placement="bottom"
               :container="container"
               @show="onShow">
      <PMColumnFilterForm @onSortAscending="onSortAscending"
                           @onSortDescending="onSortDescending"
                           @onCancel="onCancel"
                           @onApply="onApply">
      </PMColumnFilterForm>
    </b-popover>
  </div>
</template>

<script>
  import PMColumnFilterForm from "./PMColumnFilterForm"
  export default {
    components: {
      PMColumnFilterForm
    },
    props: ["container", "id"],
    data() {
      return {
        popoverShow: false
      };
    },
    created() {
    },
    mounted() {
    },
    methods: {
      onShow() {
        this.$root.$emit('bv::hide::popover')
      },
      onSortAscending() {
        this.$emit("onSortAscending", "asc");
      },
      onSortDescending() {
        this.$emit("onSortDescending", "desc");
      },
      onCancel() {
        this.popoverShow = false;
      },
      onApply() {
        this.popoverShow = false;
      },
    }
  };
</script>

<style>
</style>
<style scoped>
  .popover{
    max-width: 375px;
  }
</style>