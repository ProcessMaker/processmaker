<template>
  <div :id="id">
    <b-button :id="'pm-cff-button-'+id" 
              variant="link"
              size="sm"
              class="pm-filter-popover-button">
      <PMColumnFilterIconThreeDots
        :columnSortAsc="columnSortAsc"
        :columnSortDesc="columnSortDesc"
        :filterApplied="filterApplied"
      />
    </b-button>
    <b-popover :container="container"
               :boundary="boundary"
               :target="'pm-cff-button-'+id"
               :show.sync="popoverShow"
               triggers="click"
               placement="bottom"
               custom-class="pm-filter-popover"
               @show="onShow"
               @shown="onShown">
      <PMColumnFilterForm ref="pmColumnFilterForm"
                          :type="type"
                          :value="value"
                          :format="format"
                          :formatRange="formatRange"
                          :operators="operators"
                          :viewConfig="viewConfig"
                          :sort="sort"
                          :hideSortingButtons="hideSortingButtons"
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
      PMColumnFilterIconThreeDots,
    },
    props: [
      "container",
      "boundary",
      "id",
      "type",
      "value",
      "format",
      "formatRange",
      "operators",
      "viewConfig",
      "sort",
      "hideSortingButtons",
      "columnSortAsc",
      "columnSortDesc",
      "filterApplied",
    ],
    data() {
      return {
        popoverShow: false
      };
    },
    methods: {
      onShown() {
        this.$emit("onUpdate", this);
        this.focusCancelButton();
        this.closeOnBlur();
      },
      onShow() {
        this.$root.$emit("bv::hide::popover");
      },
      onChangeSort(value) {
        this.$emit("onChangeSort", value);
        this.popoverShow = false;
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
      },
      closeOnBlur() {
        let area = this.$refs.pmColumnFilterForm.$el.parentNode;
        area.addEventListener('mouseenter', () => {
          window.removeEventListener('click', this.onCancel);
        });
        area.addEventListener('mouseleave', () => {
          window.addEventListener('click', this.onCancel);
        });
      },
      focusCancelButton() {
        let cancel = this.$refs.pmColumnFilterForm.$el.getElementsByClassName("pm-filter-form-button-cancel");
        cancel[0].focus();
      }
    }
  };
</script>

<style>
  .pm-filter-popover .popover-body{
    padding: 0.5rem 0.75rem !important;
  }
  .pm-filter-popover-button{
    color: #1572C2 !important;
  }
</style>
<style scoped>
  .popover{
    max-width: 375px;
  }
</style>
