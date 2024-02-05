export default {
  data() {
    return {
      advancedFilter: [],
    };
  },
  mounted() {
    this.setAdvancedFilter();
    window.ProcessMaker.EventBus.$on('advanced-filter-updated', this.setAdvancedFilter);
  },
  methods: {
    setAdvancedFilter() {
      this.advancedFilter = window.Processmaker.advanced_filter || [];
    },
    formatForBadge(filters, result) {
      for(const filter of filters) {
        result.push([
          this.formatBadgeSubject(filter.subject.value),
          [{name: this.formatBadgeValue(filter), advanced_filter: true}]
        ]);

        if (filter.or && filter.or.length > 0) {
          this.formatForBadge(filter.or, result);
        }
      }
    },
    formatBadgeSubject(value) {
      const parts = value.split(".");
      let result = value;
      if (parts.length > 1) {
        result = parts[1];
      }
      result = result.replace(/_/g, " ");
      if (result === "name") {
        result = "process";
      }
      return result;
    },
    formatBadgeValue(filter) {
      let result = filter.operator;
      result = result + " " + filter.value;
      return result;
    },
  },
  computed: {
    formatAdvancedFilterForBadges() {
      if (!this.advancedFilter || this.advancedFilter.length === 0) {
        return [];
      }
      const result = [];
      this.formatForBadge(this.advancedFilter, result);
      return result;
    },
  },
}