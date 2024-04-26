<template>
  <multiselect
    :id="'api-select-' + _uid"
    :value="selectedOption"
    @input="change"
    :placeholder="placeholder"
    :options="options"
    :multiple="multiple"
    :track-by="trackBy"
    :show-labels="false"
    :searchable="true"
    :internal-search="false"
    :label="label"
    @search-change="loadOptions"
    @open="loadOptions(null)"
  >
    <template slot="noResult">
      <slot name="noResult">{{ $t('Not found') }}</slot>
    </template>
    <template slot="noOptions">
      <slot name="noOptions">{{ $t('No Data Available') }}</slot>
    </template>
  </multiselect>
</template>

<script>
import { get } from "lodash";

export default {
  props: {
    value: null,
    placeholder: String,
    trackBy: {
      type: String,
      default: "id"
    },
    label: {
      type: String,
      default: "name"
    },
    api: {
      type: String,
      default: "process"
    },
    multiple: {
      type: Boolean,
      default: false
    },
    storeId: {
      type: Boolean,
      default: true
    },
    exclude_ids: {
      type: Array,
      default() {
        return [];
      },
    },
    undefinedValue: null,
  },
  data() {
    return {
      options: [],
      selectedOption: null
    };
  },
  watch: {
    value: {
      immediate: true,
      handler(value) {
        this.selectedOption = this.storeId
          ? this.options.find(option => get(option, this.trackBy) == value)
          : value;
        value && !this.selectedOption ? this.loadSelected(value) : null;
      }
    }
  },
  methods: {
    change(value) {
      this.$emit("input", this.storeId ? get(value, this.trackBy, this.undefinedValue) : value);
    },
    loadOptions(filter) {
      const query = {
        filter: typeof filter === "string" ? filter : '',
        exclude_ids: this.exclude_ids.join(','),
      };
      const separator = this.api.includes('?') ? '&' : '?';
      window.ProcessMaker.apiClient
        .get(this.api + separator +
          Object.keys(query).
          filter(par => query[par], '').
          map(par => `${par}=${encodeURIComponent(query[par])}`).
          join('&')
        ).then(response => {
          this.options = response.data.data || [];
        });
    },
    loadSelected(value) {
      window.ProcessMaker.apiClient
        .get(this.api + "/" + value)
        .then(response => {
          this.selectedOption = response.data;
        });
    }
  }
};
</script>
