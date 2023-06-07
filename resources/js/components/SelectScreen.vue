<template>
  <multiselect
    id="search-screen-text"
    :value="value"
    :placeholder="$t('Screen')"
    :options="options"
    :multiple="multiple"
    :show-labels="false"
    :searchable="false"
    :allow-empty="false"
    track-by="id"
    label="title"
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
