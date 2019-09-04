<template>
  <multiselect
    :value="value"
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
    @open="loadOptions"
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
    }
  },
  data() {
    return {
      options: []
    };
  },
  methods: {
    change(value) {
      this.$emit("input", value);
    },
    loadOptions(filter) {
      window.ProcessMaker.apiClient
        .get(this.api + (typeof filter === "string" ? "?filter=" + filter : ""))
        .then(response => {
          this.options = response.data.data;
        });
    }
  }
};
</script>
