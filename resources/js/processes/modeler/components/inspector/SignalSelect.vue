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
        @search-change="loadOptionsDebounced"
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
        <button type="button" class="btn btn-secondary btn-sm" @click="toggleConfigSignal">
          <i class="fa fa-ellipsis-h"></i>
        </button>
      </div>
    </div>
    <small v-if="helper" class="form-text text-muted">{{ $t(helper) }}</small>
    <div v-if="showNewSignal" class="card">
      <div class="card-body p-2">
        <form-input :label="$t('ID')" v-model="signalId" :error="validateNewId(signalId)"></form-input>
        <form-input :label="$t('Name')" v-model="signalName" :error="validateNewName(signalName)"></form-input>
      </div>
      <div class="card-footer text-right p-2">
        <button type="button" class="btn-special-assignment-action btn-special-assignment-close btn btn-outline-secondary btn-sm" @click="cancelAddSignal">
          Cancel
        </button>
        <button :disabled="!validNew" type="button" class="btn-special-assignment-action btn btn-secondary btn-sm" @click="addSignal">
          Save
        </button>
      </div>
    </div>
    <div v-if="showEditSignal" class="card">
      <div class="card-body p-2">
        <form-input :label="$t('Name')" v-model="signalName" :error="validateName(signalName)"></form-input>
      </div>
      <div class="card-footer text-right p-2">
        <button type="button" class="btn-special-assignment-action btn-special-assignment-close btn btn-outline-secondary btn-sm" @click="cancelAddSignal">
          Cancel
        </button>
        <button :disabled="!validUpdate" type="button" class="btn-special-assignment-action btn btn-secondary btn-sm" @click="updateSignal">
          Save
        </button>
      </div>
    </div>
    <div v-else-if="showConfirmDelete" class="card mb-3 bg-danger text-white">
      <div class="card-body p-2">
        {{ $t('Are you sure you want to delete this item?') }}
        ({{ deleteSignal.id }}) {{ deleteSignal.name }}
      </div>
      <div class="card-footer text-right p-2">
        <button type="button" class="btn btn-sm btn-light mr-2 p-1 font-xs" @click="showConfirmDelete=false">
          Cancel
        </button>
        <button type="button" class="btn btn-sm btn-danger p-1 font-xs" @click="confirmDeleteSignal">
          Delete
        </button>
      </div>
    </div>
    <template v-else-if="showListSignals && !showNewSignal && !showEditSignal">
      <table class="table table-sm table-striped" width="100%">
        <thead>
          <tr>
            <td colspan="2" align="right">
              <button type="button" class="btn btn-secondary btn-sm p-1 font-xs" @click="showAddSignal">
                <i class="fa fa-plus"></i> Signal
              </button>
            </td>
          </tr>
        </thead>
        <tbody>
          <tr v-for="signal in localSignals" :key="`signal-${signal.id}`">
            <td>
              <b-badge variant="secondary">{{ signal.id }}</b-badge>
              {{ signal.name }}
            </td>
            <td align="right">
              <a href="javascript:void(0)" @click="editSignal(signal)"><i class="fa fa-pen ml-1"></i></a>
              <a href="javascript:void(0)" @click="removeSignal(signal)"><i class="fa fa-trash ml-1"></i></a>
            </td>
          </tr>
        </tbody>
      </table>
    </template>
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
    localSignals() {
      const signals = [];
      ProcessMaker.$modeler.definitions.rootElements.forEach((element) => {
        if (element.$type === 'bpmn:Signal') {
          signals.push({
            id: element.id,
            name: element.name
          });
        }
      });
      return signals;
    },
    validNew() {
      return this.validateNewId(this.signalId) === ''
        && this.validateNewName(this.signalName) === '';
    },
    validUpdate() {
      return this.validateName(this.signalName) === '';
    },
  },
  data() {
    return {
      pmql: 'id!=' + ProcessMaker.modeler.process.id,
      showListSignals: false,
      showNewSignal: false,
      showEditSignal: false,
      showConfirmDelete: false,
      deleteSignal: null,
      globalSignals: [],
      signalId: '',
      signalName: '',
    };
  },
  methods: {
    validateNewId(id) {
      if (!id) {
        return this.$t('Signal ID is required');
      }
      const exists = ProcessMaker.$modeler.definitions.rootElements.find((element) => {
        return element.id === id;
      });
      if (exists) {
        return this.$t('Signal ID is duplicated');
      }
      const validId = id.match(/^[a-zA-Z_][\w.-]*$/);
      if (!validId) {
        return this.$t('Signal ID is not a valid xsd:ID');
      }
      return '';
    },
    validateNewName(name) {
      if (!name) {
        return this.$t('Signal Name is required');
      }
      const exists = ProcessMaker.$modeler.definitions.rootElements.find((element) => {
        return element.$type === 'bpmn:Signal' && element.name === name;
      });
      if (exists) {
        return this.$t('Signal Name is duplicated');
      }
      return '';
    },
    validateName(name) {
      if (!name) {
        return this.$t('Signal Name is required');
      }
      return '';
    },
    confirmDeleteSignal() {
      this.showConfirmDelete = false;
      const index = ProcessMaker.$modeler.definitions.rootElements.findIndex(element => element.id === this.deleteSignal.id);
      if (index) {
        ProcessMaker.$modeler.definitions.rootElements.splice(index, 1);
      }
    },
    removeSignal(signal) {
      this.showConfirmDelete = true;
      this.deleteSignal = signal;
    },
    toggleConfigSignal() {
      this.showListSignals = !this.showListSignals;
    },
    editSignal(signal) {
      this.signalId = signal.id;
      this.signalName = signal.name;
      this.showEditSignal = true;
    },
    getSignalById(id) {
      return ProcessMaker.$modeler.definitions.rootElements.find(element => element.id === id);
    },
    change (value) {
      let signal = this.getSignalById(value.id);
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
      this.signalId = '';
      this.signalName = '';
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
    updateOptions() {
      this.options = this.globalSignals;
      ProcessMaker.$modeler.definitions.rootElements.forEach((element) => {
        const localSignal = this.options.find(option => option.id === element.id);
        if (element.$type === 'bpmn:Signal' &&!localSignal) {
          this.options.push({
            id: element.id,
            name: element.name
          });
        } else if (localSignal) {
          localSignal.name = element.name;
        }
      });
    },
    loadOptions(filter) {
      const pmql = this.pmql;
      window.ProcessMaker.apiClient
        .get(this.api, { params: { filter, pmql } })
        .then(response => {
          this.globalSignals = response.data.data || [];
          this.updateOptions();
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

<style scoped>
  .font-xs {
    font-size: 0.75rem;
  }
</style>