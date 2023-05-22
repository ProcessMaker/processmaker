<template>
  <div class="data-format-selector">
    <multiselect
      v-model="format"
      track-by="value"
      label="content"
      :show-labels="false"
      :placeholder="$t('Data Format')"
      :options="formats"
      :multiple="false"
      :searchable="true"
      :internal-search="true"
      :allow-empty="false"
      >
    </multiselect>
  </div>
</template>

<script>
import DataFormats from '../../data-formats';

export default {
  props: {
    value: {
      type: String,
      default: 'string'
    }
  },
  data() {
    return {
      format: null,
      formats: DataFormats.formats(),
    };
  },
  mounted() {
    this.format = DataFormats.format(this.value);
    this.formats.map(format => format.content = this.$t(format.content));
  },
  watch: {
    format() {
      this.$emit('input', this.format.value);
    }
  }
}
</script>

<style lang="scss">
  .data-format-selector {
    
    $multiselect-height: 38px;
    
    .multiselect {
      display: inline-block !important;
    }
    
    .multiselect,
    .multiselect__tags {
      height: $multiselect-height;
      min-height: $multiselect-height;
      max-height: $multiselect-height;
    }
    
    .multiselect__placeholder {
      display: block;
      line-height: 20px;
      margin: 0;
      margin-bottom: 10px;
      padding-bottom: 2px;
      padding-left: 5px;
      padding-top: 0;
    }

    .multiselect__single {
      padding-bottom: 2px;
      padding-top: 0;
    }

    .multiselect__tags {
      font-size: 16px;
    }
    
    .multiselect__option--highlight {
      background: #ddd;
      color: #222;
    }
    
    .form-control-multiselect {
      position: relative;
      -webkit-box-flex: 1;
          -ms-flex: 1 1 0%;
              flex: 1 1 0%;
      min-width: 0;
      margin-bottom: 0;
    }
  }
</style>