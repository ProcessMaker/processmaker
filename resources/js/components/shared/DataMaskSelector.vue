<template>
  <div class="data-mask-selector">
    <multiselect
      v-model="mask"
      track-by="code"
      label="code"
      :show-labels="false"
      :placeholder="$t('Data Format')"
      :options="masks"
      :multiple="false"
      :searchable="true"
      :internal-search="true"
      >
    </multiselect>
  </div>
</template>

<script>
import DataFormats from '../../data-formats';

export default {
  props: {
    value: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      mask: null,
      masks: DataFormats.masks(),
    };
  },
  mounted() {
    this.mask = DataFormats.mask(this.value);
  },
  watch: {
    mask() {
      this.$emit('input', this.mask);
    }
  }
}
</script>

<style lang="scss">
  .data-mask-selector {
    
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