<template>
    <multiselect id="search-status-text"
                :value="value"
                @input="change"
                :placeholder="$t('Select a status')"
                :options="options"
                :multiple="multiple"
                :show-labels="false"
                :searchable="false"
                :allow-empty="false"
                class="assignable-input">
        <template slot="noResult" >
            <slot name="noResult">{{ $t('No elements found. Consider changing the search query.') }}</slot>
        </template>
        <template slot="noOptions" >
            <slot name="noOptions">{{ $t('No Data Available') }}</slot>
        </template>
    </multiselect>
</template>

<script>
export default {
  props: {
    value: null,
    multiple: {
      type: Boolean,
      default: false
    },
    options: {
        type: Array,
        default: () => ['ACTIVE', 'INACTIVE']
    }
  },
  methods: {
    change(value) {
      this.$emit('input', value);
    }
  }
};
</script>
