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
      this.advancedFilter = get(this.advancedFilterProp, 'filters') || 
              get(window, 'ProcessMaker.advanced_filter.filters', []);
      this.$refs.pmqlInputFilters?.buildPmql();
    },
    formatForBadge(filters, result) {
      for(const filter of filters) {
        if (filter._hide_badge) {
          continue;
        }
        result.push([
          this.formatBadgeSubject(filter),
          [
            {
              name: this.formatBadgeValue(filter),
              operator: filter.operator,
              advanced_filter: true
            }
          ]
        ]);

        if (filter.or && filter.or.length > 0) {
          this.formatForBadge(filter.or, result);
        }
      }
    },
    formatBadgeSubject(filter) {
      return get(filter, '_column_label', get(filter, 'subject.value', ''));
    },
    formatBadgeValue(filter) {
      if ('_display_value' in filter) {
        return filter._display_value;
      }
      if (this.isDatetime(filter.value)) {
        return this.formatDatetime(filter.value);
      }
      if (Array.isArray(filter.value)) {
        let copyArray = [...filter.value];
        for (let i = 0; i < copyArray.length; i++) {
          let cell = copyArray[i];
          if (this.isDatetime(cell)) {
            copyArray[i] = this.formatDatetime(cell);
          }
        }
        return copyArray;
      }
      return filter.value;
    },
    isDatetime(value) {
      let date = new Date(value);
      return date instanceof Date && !isNaN(date);
    },
    formatDatetime(value) {
      return moment(value)
              .tz(window.ProcessMaker.user.timezone)
              .format(window.ProcessMaker.user.datetime_format);
    }
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