<template>
  <div>
    <div class="form-group">
      <label>Signal Payload</label>
        <multiselect
            v-model="selectedPayloadType"
            @input="payloadChange"
            placeholder="Select Option"
            :options="payloadTypes"
            track-by="id"
            label="name"
            :show-labels="false"
            :searchable="true"
            :internal-search="false"
        >
          <template slot="noResult">
            <slot name="noResult">{{ $t('Not found') }}</slot>
          </template>
          <template slot="noOptions">
            <slot name="noOptions">{{ $t('No Data Available') }}</slot>
          </template>
        </multiselect>
    </div>

    <div class="form-group" v-if="showVariable">
      <label>{{ variableLabel }}</label>
      <input class="form-control" type="text" v-model="config.payload[0].variable">
      <small class="form-text text-muted">{{ variableHelper }}</small>
    </div>
    <div class="form-group" v-if="showExpression">
      <label>{{$t('Expression')}}</label>
      <input class="form-control" type="text" v-model="config.payload[0].expression">
    </div>
  </div>
</template>

<script>

export default {
  props: ['value'],
  data() {
    return {
      config: {
        payload: [{
          id: 'ALL_REQUEST_DATA',
          variable: '',
          expression: ''
        }]
      },
      selectedPayloadType: null
    }
  },
  computed: {
    showVariable() {
      return this.config.payload && this.config.payload.length > 0 &&
          (this.config.payload[0].id === 'REQUEST_VARIABLE' || this.config.payload[0].id === 'EXPRESSION');
    },
    showExpression() {
      return this.config.payload && this.config.payload.length > 0 && this.config.payload[0].id === 'EXPRESSION';
    },
    variableLabel() {
      return this.config.payload && this.config.payload.length > 0 && this.config.payload[0].id === 'EXPRESSION'
        ? this.$t("Name")
        : this.$t("Request Variable")
    },
    variableHelper() {
      return this.config.payload && this.config.payload.length > 0 && this.config.payload[0].id === 'EXPRESSION'
          ? this.$t("Name to identify the expression result.")
          : this.$t("Name of the request variable to send as payload.")
    },
    payloadTypes() {
      return [
        { id: 'NONE', name: this.$t('No Request Data') },
        { id: 'ALL_REQUEST_DATA', name: this.$t('All Request Data') },
        { id: 'REQUEST_VARIABLE', name: this.$t('Specify Request Variable') },
      ];
    },
  },
  watch: {
    'config.payload': {
      deep:true,
      handler(value) {
        if (value && value.length > 0) {
          const firstElem = value[0];
          this.selectedPayloadType = this.payloadTypes.find(type => type.id == firstElem.id) || null;
        }
        else {
          this.selectedPayloadType = null;
        }
      },
    },
    config: {
      deep: true,
      handler()  {
        if (this.node()) {
          this.node().eventDefinitions.forEach(definition => {
            if(definition.$type === 'bpmn:SignalEventDefinition') {
              definition.config = JSON.stringify(this.config);
            }
          }, this)
        }
      }
    }
  },
  methods: {
    payloadChange(selectedObject) {
      this.config.payload[0].id = selectedObject.id;
    },
    node() {
      const modeler =  this.$root.$children[0].$refs.modeler;
      return modeler.highlightedNode.definition;
    },
    loadConfig() {
      if (this.node().eventDefinitions && this.node().eventDefinitions.length > 0 && this.node().eventDefinitions[0].config) {
        this.config = JSON.parse(_.get(this.node().eventDefinitions[0], 'config'));
      }
    },
  },
  mounted() {
    this.loadConfig();
  }
}
</script>

<style scoped>
  .striped {
    background-color: rgba(0,0,0,.05);
  }
  .add-button {
    padding: 0;
    height: 14px;
    width: 13px;
    line-height: 0;
  }
  .helper-text {
    font-size: 12px;
  }

  .displayed-expression {
    width: 146px;
  }
  
  .displayed-expression,
  .special-assignment-input {
    font-family: monospace;
  }

  .assignment-list {
    font-size:13px;
  }
</style>