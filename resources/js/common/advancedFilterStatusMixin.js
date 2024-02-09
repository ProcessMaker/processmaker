import { get } from "lodash";

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
      this.advancedFilter = get(window, 'ProcessMaker.advanced_filter.filters', []);
    },
    formatForBadge(filters, result) {
      for(const filter of filters) {
        result.push([
          this.formatBadgeSubject(filter),
          [{name: this.formatBadgeValue(filter), advanced_filter: true}]
        ]);

        if (filter.or && filter.or.length > 0) {
          this.formatForBadge(filter.or, result);
        }
      }
    },
    formatBadgeSubject(filter) {
      return get(filter, '_column_label', '');
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