<template>
  <div :id="id">
    <b-button :id="'pm-cff-button-'+id" 
              variant="light"
              size="sm">
      <PMColumnFilterIconThreeDots></PMColumnFilterIconThreeDots>
    </b-button>
    <b-popover :target="'pm-cff-button-'+id"
               triggers="click"
               :show.sync="popoverShow"
               placement="bottom"
               :container="container"
               @show="onShow"
               custom-class="pm-filter-popover">
      <PMColumnFilterForm :type="type"
                          :value="value"
                          @onSortAscending="onSortAscending"
                          @onSortDescending="onSortDescending"
                          @onApply="onApply"
                          @onClear="onClear"
                          @onCancel="onCancel">
      </PMColumnFilterForm>
    </b-popover>
  </div>
</template>

<script>
  import PMColumnFilterForm from "./PMColumnFilterForm"
  import PMColumnFilterIconThreeDots from "./PMColumnFilterIconThreeDots"

  export default {
    components: {
      PMColumnFilterForm,
      PMColumnFilterIconThreeDots
    },
    props: ["id", "container", "type", "value"],
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
        this.$root.$emit("bv::hide::popover")
      },
      onSortAscending() {
        this.$emit("onSortAscending", "asc");
      },
      onSortDescending() {
        this.$emit("onSortDescending", "desc");
      },
      onApply(json) {
        this.popoverShow = false;
        this.$emit("onApply", json);
      },
      onClear() {
        this.$emit("onClear");
      },
      onCancel() {
        this.popoverShow = false;
        this.$emit("onCancel");
      },
    }
  };
</script>

<style>
  .pm-filter-popover .popover-body{
    padding: 0.5rem 0.75rem !important;
  }
</style>
<style scoped>
  .popover{
    max-width: 375px;
  }
</style>
