<template>
  <multiselect
    id="search-language-text"
    :value="value"
    :placeholder="$t('Select a target language')"
    :options="options"
    :multiple="multiple"
    :show-labels="false"
    :searchable="true"
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
  props: ["value", "multiple", "options"],
  data() {
    return {
    };
  },
  mounted() {
  },
  methods: {
    change(value) {
      this.$emit("input", value);
    },
  },
};
</script>
