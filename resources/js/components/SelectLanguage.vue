<template>
  <multiselect
    id="search-language-text"
    :value="value"
    :placeholder="$t('Select a target language')"
    :options="options"
    :multiple="multiple"
    :show-labels="false"
    :searchable="false"
    :allow-empty="false"
    track-by="language"
    label="humanLanguage"
    class="assignable-input"
    @input="change"
  >
    <template slot="noResult">
      <slot name="noResult">
        {{ $t('No elements found. Consider changing the search query.') }}
      </slot>
    </template>
    <template slot="noOptions">
      <slot name="noOptions">
        {{ $t('No Data Available') }}
      </slot>
    </template>
  </multiselect>
</template>

<script>
export default {
  props: {
    value: null,
    multiple: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      options: [],
    };
  },
  mounted() {
    this.fetch();
  },
  methods: {
    change(value) {
      this.$emit("input", value);
    },
    fetch() {
      this.loading = true;

      // Load from our api client
      ProcessMaker.apiClient
        .get("process/translations/languages")
        .then((response) => {
          this.options = JSON.parse(JSON.stringify(response.data.availableLanguages));
          this.loading = false;
        });
    },
  },
};
</script>
