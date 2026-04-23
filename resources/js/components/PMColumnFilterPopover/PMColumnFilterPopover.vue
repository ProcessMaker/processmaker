<template>
  <div :id="id">
    <b-button
      :id="'pm-cff-button-'+id"
      variant="link"
      size="sm"
      class="pm-filter-popover-button"
    >
      <PMColumnFilterIconThreeDots
        :column-sort-asc="columnSortAsc"
        :column-sort-desc="columnSortDesc"
        :filter-applied="filterApplied"
        :column-mouseover="columnHover"
      />
    </b-button>
    <b-popover
      :container="container"
      :boundary="boundary"
      :target="'pm-cff-button-'+id"
      :show.sync="popoverShow"
      triggers="click"
      placement="bottom"
      custom-class="pm-filter-popover"
      @show="onShow"
      @shown="onShown"
    >
      <PMColumnFilterForm
        ref="pmColumnFilterForm"
        :type="type"
        :value="value"
        :format="format"
        :format-range="formatRange"
        :operators="operators"
        :view-config="viewConfig"
        :sort="sort"
        :hide-sorting-buttons="hideSortingButtons"
        @onChangeSort="onChangeSort"
        @onApply="onApply"
        @onClear="onClear"
        @onCancel="onCancel"
      />
    </b-popover>
  </div>
</template>

<script>
import PMColumnFilterForm from "./PMColumnFilterForm";
import PMColumnFilterIconThreeDots from "./PMColumnFilterIconThreeDots";

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
    "columnMouseover",
  ],
  data() {
    return {
      popoverShow: false,
    };
  },
  computed: {
    columnHover() {
      return this.columnMouseover === this.value;
    },
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
      const area = this.$refs.pmColumnFilterForm.$el.parentNode;
      area.addEventListener("mouseenter", () => {
        window.removeEventListener("click", this.onCancel);
      });
      area.addEventListener("mouseleave", () => {
        window.addEventListener("click", this.onCancel);
      });
    },
    focusCancelButton() {
      const cancel = this.$refs.pmColumnFilterForm.$el.getElementsByClassName("pm-filter-form-button-cancel");
      cancel[0].focus();
    },
  },
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
