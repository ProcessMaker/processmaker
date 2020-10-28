<template>
    <div class="w-100">
      <div :key="item.field" class="column-card card card-body p-2 my-3 w-100">
          <div class="d-flex flex-row justify-content-between ">
              <div>
                  <div class="handle pl-1 pr-2">
                      <i class="fa fa-fw fa-bars handle-bars"></i> {{ $t(format) }}
                  </div>
                  <span class="column-label">{{ item.label }}</span>
              </div>
              <div>        
                <a v-show="!withoutConfig" class="text-primary column-button" v-b-modal.column-modal v-if="! item.default" @click="onConfig"><i class="fa fa-cog fa-fw"></i></a>
                <a v-show="!withoutRemove" class="text-primary column-button" @click="onRemove"><i class="fa fa-times fa-fw"></i></a>
              </div>
          </div>
      </div>
    </div>
</template>

<script>
import DataFormats from '../../data-formats';

export default {
  props: ["column","withoutConfig","withoutRemove"],
  data() {
    return {
      item: this.column
    }
  },
  computed: {
    format() {
      let format = DataFormats.format(this.item.format);
      if (format.value == 'currency') {
        let mask = DataFormats.mask(this.item.mask)
        if (mask && mask.code) {
          return `${format.content} (${mask.code})`;
        }
      }
      return format.content;
    }
  },
  methods: {
    onConfig() {
      this.$emit('config', this.item);
    },
    onRemove() {
      this.$emit('remove', this.item);
    },
    dataFormat(value) {
      return DataFormats.format(value).content;
    }
  }
};
</script>
<style>
  .column-card {
    cursor: grab;
  }
  
  .handle {
    background: #dfdfdf;
    color: #555;
    height: 100%;
    left: 0;
    overflow: hidden;
    padding-top: 8px;
    position: absolute;
    top: 0;
    width: 175px;
  }
  
  .handle-bars {
    color: #999;
    margin-left: 8px;
    margin-right: 6px;
  }
  
  .column-label {
    padding-left: 181px;
  }

  .column-card a {
    cursor: pointer;
  }
  
  .column-card:active,
  .column-card:focus {
    cursor: grabbing;
  }
  
  .column-add,
  .column-add:active,
  .column-add:focus {
    cursor: pointer;
  }
</style>