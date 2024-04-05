<template>
  <div style="display: flex;">
    <div v-if="filterBadges.length > 0"
         class="selected-filters-bar d-flex pt-2">

      <span v-for="filter in filterBadges"
            class="selected-filter-item d-flex align-items-center">

        <span class="selected-filter-key mr-1">
          {{ $t(capitalizeString(filter[0])) }}
          <template v-if="!get(filter, '1.0.advanced_filter', false)">:</template>
        </span>
        {{ filter[1][0].operator ?? '' }}
        <template v-if="filter[0] === 'Status'">
          {{ $t(filter[1][0].name) }}
        </template>
        <template v-else>
          {{ filter[1][0].name ? filter[1][0].name : filter[1][0].fullname }}
        </template>

        <span v-if="filter[1].length > 1"
              class="badge badge-pill ml-2 filter-counter">
          +{{ filter[1].length - 1 }}
        </span>

        <i v-if="!get(filter, '1.0.advanced_filter', false)"
           role="button"
           class="fa fa-times pl-2 pr-0">
        </i>

      </span>

    </div>
    <div style="margin-left: auto">
      <slot name="right-of-badges" />
    </div>
  </div>
</template>

<script>
  import { get } from 'lodash';
  export default {
    props: [
      "value",
      "advancedFilterProp",
      "showPmqlBadge"
    ],
    data() {
      return {
        get: get,
        pmql: "",
        selectedFilters: [],
        query: "",
        advancedFilter: []
      };
    },
    mounted() {
      window.ProcessMaker.EventBus.$on('advanced-filter-updated', this.setAdvancedFilter);

      this.setAdvancedFilter();
    },
    computed: {
      filterBadges() {
        let result = [
          ...this.pmqlBadge,
          ...this.selectedFilters,
          ...this.formatAdvancedFilterForBadges
        ];
        return result;
      },
      pmqlBadge() {
        let result = [];
        if (this.value) {
          result.push([
            'pmql',
            [
              {
                name: this.value,
                operator: '',
                advanced_filter: true
              }
            ]
          ]);
        }
        return result;
      },
      formatAdvancedFilterForBadges() {
        let result = [];
        if (Array.isArray(this.advancedFilter)) {
          this.formatForBadge(this.advancedFilter, result);
        }
        return result;
      }
    },
    watch: {
      advancedFilterProp: {
        deep: true,
        handler(a) {
          this.setAdvancedFilter();
        }
      }
    },
    methods: {
      setAdvancedFilter() {
        this.advancedFilter = get(this.advancedFilterProp, 'filters') ||
                get(window, 'ProcessMaker.advanced_filter.filters', []);
      },
      capitalizeString(string) {
        if (string === "") {
          return "";
        }
        let str = string.toLowerCase();
        return str.charAt(0).toUpperCase() + str.slice(1);
      },
      formatForBadge(filters, result) {
        for (let filter of filters) {
          if (filter._hide_badge) {
            continue;
          }
          result.push([
            this.formatBadgeSubject(filter),
            [
              {
                name: filter.value,
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
      }
    }
  }
</script>

<style lang="scss">
</style>