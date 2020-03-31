<template>
  <div>
    <label class="typo__label">{{ label }}</label>
    <div class="d-flex">
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
      <div class="btn-group ml-1" role="group">
        <button type="button" class="btn btn-secondary btn-sm" @click="showAddSignal">
          <i class="fa fa-plus"></i>
        </button>
        <button v-if="value" type="button" class="btn btn-secondary btn-sm" @click="editSignal">
          <i class="fa fa-pen"></i>
        </button>
      </div>
    </div>
    <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
    <div v-if="showNewSignal" class="card">
      <div class="card-body p-2">
        <form-input :label="$t('ID')" v-model="signalId"></form-input>
        <form-input :label="$t('Name')" v-model="signalName"></form-input>
      </div>
      <div class="card-footer text-right p-2">
        <button type="button" class="btn-special-assignment-action btn-special-assignment-close btn btn-outline-secondary btn-sm" @click="cancelAddSignal">
          Cancel
        </button>
        <button :disabled="!valid" type="button" class="btn-special-assignment-action btn btn-secondary btn-sm" @click="addSignal">
          Save
        </button>
      </div>
    </div>
    <div v-if="showEditSignal" class="card">
      <div class="card-body p-2">
        <form-input :label="$t('Name')" v-model="signalName"></form-input>
      </div>
      <div class="card-footer text-right p-2">
        <button type="button" class="btn-special-assignment-action btn-special-assignment-close btn btn-outline-secondary btn-sm" @click="cancelAddSignal">
          Cancel
        </button>
        <button :disabled="!valid" type="button" class="btn-special-assignment-action btn btn-secondary btn-sm" @click="updateSignal">
          Save
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import Multiselect from 'vue-multiselect';
import multiselectApi from '../../../../components/common/mixins/multiselectApi';
import {get} from 'lodash';

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
  computed: {
    valid() {
      return String(this.signalId) !== '' && String(this.signalName) !== '';
    },
  },
  data() {
    return {
      pmql: 'id!=' + ProcessMaker.modeler.process.id,
      showNewSignal: false,
      showEditSignal: false,
      signalId: '',
      signalName: '',
    };
  },
  methods: {
    editSignal() {
      const signal = this.getSignalById(this.value);
      this.signalId = signal.id;
      this.signalName = signal.name;
      this.showEditSignal = true;
    },
    getSignalById(id) {
      return ProcessMaker.$modeler.definitions.rootElements.find(element => element.id === id);
    },
    change (value) {
      let signal = this.getSignalById(value.signalRef);
      if (!signal) {
        signal = ProcessMaker.$modeler.moddle.create('bpmn:Signal', {
          id: value.id,
          name: value.name,
        });
        ProcessMaker.$modeler.definitions.rootElements.push(signal);
      }
      this.$emit('input', this.storeId ? get(value, this.trackBy) : value);
    },
    showAddSignal() {
      this.showNewSignal = true;
    },
    cancelAddSignal() {
      this.showNewSignal = false;
      this.showEditSignal = false;
    },
    updateSignal() {
      this.getSignalById(this.signalId).name = this.signalName;
      this.showEditSignal = false;
    },
    addSignal() {
      const signal = ProcessMaker.$modeler.moddle.create('bpmn:Signal', {
        id: this.signalId,
        name: this.signalName,
      });
      ProcessMaker.$modeler.definitions.rootElements.push(signal);
      this.showNewSignal = false;
    },
    loadOptions(filter) {
      const pmql = this.pmql;
      window.ProcessMaker.apiClient
        .get(this.api, { params: { filter, pmql } })
        .then(response => {
          this.options = response.data.data || [];
          ProcessMaker.$modeler.definitions.rootElements.forEach((element) => {
            if (
              element.$type === 'bpmn:Signal' &&
              !this.options.find(option => option.id === element.id)
            ) {
              this.options.push({
                id: element.id,
                name: element.name
              });
            }
          });
        });
    },
    loadSelected (value) {
      const signal = ProcessMaker.$modeler.definitions.rootElements.find(element => element.id === value);
      if (signal) {
        this.selectedOption = {
          id: signal.id,
          name: signal.name,
        };
      } else {
        window.ProcessMaker.apiClient
          .get(`${this.api}/${value}`)
          .then((response) => {
            this.selectedOption = response.data;
          });
      }
    },
  },
};
</script>
