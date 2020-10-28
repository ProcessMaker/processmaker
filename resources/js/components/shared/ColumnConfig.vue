<template>
  <b-modal
      ref="columnModal"
      id="columnModal"
      :title="title"
      v-model="show"
      header-close-content="&times;"
  >
    <b-form-group :label="$t('Label')" class="mb-4">
      <b-input v-model="column.label" ref="labelInput"></b-input>
    </b-form-group>
    <b-form-group :label="$t('Field')" class="mb-4">
      <b-input v-model="column.field"></b-input>
    </b-form-group>
    <b-form-group :label="$t('Format')" class="mb-4">
      <data-format-selector v-model="column.format"></data-format-selector>
    </b-form-group>
    <b-form-group :label="$t('Currency Format')" class="mb-4" v-if="column.format == 'currency'">
      <data-mask-selector v-model="column.mask"></data-mask-selector>
    </b-form-group>
    <b-form-group class="mb-4">
      <b-form-checkbox v-model="column.sortable" switch>
          {{ $t('Sortable') }}
      </b-form-checkbox>
    </b-form-group>
    <div slot="modal-footer">
      <button type="button" class="btn btn-outline-secondary" @click="onCancel">
          {{ $t('Cancel') }}
      </button>
      <button type="button" class="btn btn-secondary ml-2" @click="onSave"
              :disabled="! changed">
          {{ $t('Save')}}
      </button>
    </div>
  </b-modal>
</template>

<script>
import DataFormatSelector from './DataFormatSelector';
import DataMaskSelector from './DataMaskSelector';

export default {
  components: {
    DataFormatSelector,
    DataMaskSelector
  },
  data() {
    return {
      disabled: true,
      changed: false,
      index: null,
      title: null,
      original: {
        label: null,
        field: null,
        sortable: null,
        format: null,
        mask: null,
      },
      column: {
        label: null,
        field: null,
        sortable: null,
        format: 'string',
        mask: null,
        default: false
      }
    }
  },
  computed: {
    show: {
      get: function() {
        if (this.$parent.configShown) {
          this.onShow();
        }
        return this.$parent.configShown;
      },
      set: function(value) {
        this.$parent.configShown = value;
      }
    }
  },
  watch: {
    column: {
      handler: function() {
        let changed = false;
        if (this.original.label !== this.column.label) changed = true;
        if (this.original.field !== this.column.field) changed = true;
        if (this.original.sortable !== this.column.sortable) changed = true;
        if (this.original.format !== this.column.format) changed = true;
        if (this.original.mask !== this.column.mask) changed = true;
        this.changed = changed;
        
        if (this.column.format !== 'currency') {
          this.column.mask = null;
        }
      },
      deep: true
    }
  },
  methods: {
    onShow() {
      this.index = this.$parent.columnToConfig.index;
      
      this.original.label = this.$parent.columnToConfig.data.label;
      this.original.field = this.$parent.columnToConfig.data.field;
      this.original.sortable = this.$parent.columnToConfig.data.sortable;
      this.original.format = this.$parent.columnToConfig.data.format ? this.$parent.columnToConfig.data.format : 'string';
      this.original.mask = this.$parent.columnToConfig.data.mask ? this.$parent.columnToConfig.data.mask : null;

      if (this.$parent.columnToConfig.new) {
        this.title = this.$t('Create Custom Column');
      } else {
        this.title = this.$t('Configure {{name}}', {name: this.original.label});
      }
      
      this.column.label = this.original.label;
      this.column.field = this.original.field;
      this.column.sortable = this.original.sortable;
      this.column.format = this.original.format;
      this.column.mask = this.original.mask;
      
      setTimeout(() => {
        this.$refs.labelInput.focus();
      }, 0);
    },
    onSave() {
      if (this.$parent.columnToConfig.new) {
        this.$emit('create', this.column);
      } else {
        this.$emit('update', this.index, this.column);
      }
    },
    onCancel() {
      this.show = false;
    }
  }
};
</script>
<style lang="scss">
  .modal-title {
    margin-left: 0;
  }
  
  .modal-footer {
    padding: 10px 15px 15px 15px;
  }
</style>