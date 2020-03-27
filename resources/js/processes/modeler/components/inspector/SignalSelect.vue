<template>
  <div>
    <label class="typo__label">{{ label }}</label>
    <multiselect
      :value="selectedOption"
      @input="change"
      :placeholder="placeholder"
      :options="options"
      :multiple="multiple"
      :track-by="trackBy"
      :show-labels="false"
      :searchable="true"
      :internal-search="false"
      label="name"
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
    <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
  </div>
</template>

<script>
import Multiselect from 'vue-multiselect';
import multiselectApi from '../../../../components/common/mixins/multiselectApi';

export default {
  mixins: [multiselectApi],
  components: { Multiselect },
  props: {
    helper: String,
    api: {
      type: String,
      default: 'signals',
    },
  },
  data() {
    return {
    };
  },
};
</script>
