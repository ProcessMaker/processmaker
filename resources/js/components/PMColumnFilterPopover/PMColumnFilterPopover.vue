<template>
  <div :id="id">
    <b-button :id="'pm-cff-button-'+id" 
              variant="light"
              size="sm">
      <PMColumnFilterIconThreeDots></PMColumnFilterIconThreeDots>
    </b-button>
    <b-popover :container="container"
               :target="'pm-cff-button-'+id"
               :show.sync="popoverShow"
               triggers="click"
               placement="bottom"
               custom-class="pm-filter-popover"
               @show="onShow">
      <PMColumnFilterForm ref="pmColumnFilterForm"
                          :type="type"
                          :value="value"
                          :format="format"
                          :formatRange="formatRange"
                          :operators="operators"
                          :viewConfig="viewConfig"
                          :sort="sort"
                          @onChangeSort="onChangeSort"
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
    props: ["container", "id", "type", "value", "format", "formatRange", "operators", "viewConfig", "sort"],
    data() {
      return {
        popoverShow: false
      };
    },
    updated() {
      this.$emit("onUpdate", this);
    },
    methods: {
      onShow() {
        this.$root.$emit("bv::hide::popover");
      },
      onChangeSort(value) {
        this.$emit("onChangeSort", value);
      },
      onApply(json) {
        this.popoverShow = false;
        this.$emit("onApply", json);
      },
      onClear() {
        this.popoverShow = false;
        this.$emit("onClear");
      },
      onCancel() {
        this.popoverShow = false;
        this.$emit("onCancel");
      }
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
