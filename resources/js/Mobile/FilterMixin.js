const FilterMixin = {
  methods: {
    onFiltersPmqlChange(value) {
      this.fullPmql = this.getFullPmql();
      this.onSearch();
    },
    onSearch() {
      if (this.$refs.taskMobileList) {
        this.$refs.taskMobileList.fetch(true);
      }
    },
    getFullPmql() {
      let fullPmqlString = "";

      if (this.filtersPmql && this.filtersPmql !== "") {
        fullPmqlString = this.filtersPmql;
      }

      if (fullPmqlString !== "" && this.pmql && this.pmql !== "") {
        fullPmqlString = `${fullPmqlString} AND ${this.pmql}`;
      }

      if (fullPmqlString === "" && this.pmql && this.pmql !== "") {
        fullPmqlString = this.pmql;
      }

      return fullPmqlString;
    },
  },
};

export default FilterMixin;
