import { get } from "lodash";

export default {
  props: {
    advancedFilterProp: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      advancedFilter: [],
    };
  },
  mounted() {
    this.setAdvancedFilter();
    window.ProcessMaker.EventBus.$on('advanced-filter-updated', this.setAdvancedFilter);
  },
  watch: {
    advancedFilterProp: {
      deep: true,
      handler() {
        this.setAdvancedFilter();
      }
    }
  },
  methods: {
    setAdvancedFilter() {
      this.advancedFilter = get(this.advancedFilterProp, 'filters') || get(window, 'ProcessMaker.advanced_filter.filters', []);
      const doNotFetchOnPmqlChange = true; 
      this.$refs.pmqlInputFilters?.buildPmql(doNotFetchOnPmqlChange);
    },
    formatForBadge(filters, result) {
      for(const filter of filters) {
        if (filter._hide_badge) {
          continue;
        }
        result.push([
          this.formatBadgeSubject(filter),
          [{name: filter.value, operator: filter.operator, advanced_filter: true}]
        ]);

        if (filter.or && filter.or.length > 0) {
          this.formatForBadge(filter.or, result);
        }
      }
    },
    formatBadgeSubject(filter) {
      return get(filter, '_column_label', get(filter, 'subject.value', ''));
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