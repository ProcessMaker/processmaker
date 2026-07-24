<template>
  <b-form-group>
    <b-button variant="light"
              @click="onClick('asc')"
              :pressed.sync="viewToggleAsc"
              squared
              size="sm"
              class="pm-column-filter-toogle-asc-desc-button">
      <PMColumnFilterIconAsc></PMColumnFilterIconAsc>
      {{$t("Sort Ascending")}}
    </b-button>
    <b-button variant="light"
              @click="onClick('desc')"
              :pressed.sync="viewToggleDesc"
              squared
              size="sm"
              class="pm-column-filter-toogle-asc-desc-button">
      <PMColumnFilterIconDesc></PMColumnFilterIconDesc>
      {{$t("Sort Descending")}}
    </b-button>
  </b-form-group>
</template>

<script>
  import PMColumnFilterIconAsc from "./PMColumnFilterIconAsc";
  import PMColumnFilterIconDesc from "./PMColumnFilterIconDesc";

  export default {
    components: {
      PMColumnFilterIconAsc,
      PMColumnFilterIconDesc
    },
    props: [
      "value"
    ],
    data() {
      return {
        input: null,
        viewToggleAsc: false,
        viewToggleDesc: false
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.input = newValue;
          this.toogle(newValue);
        },
        immediate: true
      },
      input() {
        this.$emit("input", this.input);
        this.$emit("onChange", this.input);
        this.toogle(this.input);
      }
    },
    methods: {
      onClick(value) {
        this.input = value;
      },
      toogle(value) {
        if (value === null || value === undefined) {
          this.viewToggleDesc = false;
          this.viewToggleAsc = false;
        } else {
          this.viewToggleDesc = value.toLowerCase() === "desc";
          this.viewToggleAsc = value.toLowerCase() === "asc";
        }
      }
    }
  };
</script>

<style scoped>
  .pm-column-filter-toogle-asc-desc-button{
    text-transform: lowercase;
  }
</style>
