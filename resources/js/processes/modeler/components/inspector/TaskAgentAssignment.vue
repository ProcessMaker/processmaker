<template>
  <div>
    <!-- Enable Agent Participant -->
    <form-checkbox
      :label="$t('Enable Agent Participant')"
      :checked="isEnabled"
      toggle="true"
      @change="setEnabled"
    />

    <!-- Agent Selection -->
    <div v-if="isEnabled" class="form-group mt-3">
      <label>{{ $t('Select Agent') }}</label>
      <select 
        class="form-control"
        v-model="selectedAgent">
        <option v-for="agent in agentList" 
                :key="agent.value" 
                :value="agent.value">
          {{ agent.label }}
        </option>
      </select>
    </div>

    <!-- Conditional Assignment -->
    <div v-if="isEnabled" class="form-group mt-3">
      <label>{{ $t('Conditional Assignment') }}</label>
      <monaco-editor
        v-model="conditionalAssignment"
        :options="monacoOptions"
        class="monaco-editor"
      />
      <small class="form-text text-muted d-block mb-2">
        {{ $t('Include a prompt to help the Agent determine whether to claim the task or not.') }}
      </small>
    </div>

    <!-- Action Buttons -->
    <div v-if="isEnabled" class="d-flex mt-3 btn-group" role="group">
      <b-button
        variant="outline-secondary"
        class="flex-grow-1 me-2"
        :class="{ active: actionType === 'prefill' }"
        @click="setActionType('prefill')"
      >
        {{ $t('Prefill') }}
      </b-button>
      <b-button
        variant="outline-secondary"
        class="flex-grow-1"
        :class="{ active: actionType === 'submit' }"
        @click="setActionType('submit')"
      >
        {{ $t('Submit') }}
      </b-button>
    </div>

    <!-- Prefill Section -->
    <div v-if="isEnabled && actionType === 'prefill'" class="mt-3">
      <small class="text-muted d-block mb-2">
        {{ $t('The agent will prefill the variables that were defined, and present them to the corresponding participant.') }}
      </small>

      <!-- Variables to Fill -->
      <div class="form-group">
        <label>{{ $t('Define Variables to Fill') }}</label>
        <monaco-editor
          v-model="variablesToFill"
          :options="monacoOptions"
          class="monaco-editor"
        />
      </div>
    </div>

    <!-- Autonomy Slider -->
    <div v-if="isEnabled" class="form-group mt-3">
      <label>{{ $t('Autonomy') }}</label>
      <small class="form-text text-muted d-block mb-2">
        {{ $t('Set how independent is the agent when completing or filling a task.') }}
      </small>
      <label class="form-label">{{ autonomyLevel }}</label>
      <b-form-input
        v-model="autonomyLevel"
        type="range"
        min="0"
        max="100"
      />
      <div class="d-flex justify-content-between">
        <small>0</small>
        <small>100</small>
      </div>
    </div>

    <!-- Learn From Previous Cases -->
    <div v-if="isEnabled" class="mt-3">
      <form-checkbox
        :label="$t('Learn From Previous Cases')"
        :checked="learnFromPrevious"
        toggle="true"
        @change="setLearnFromPrevious"
      />
      <div v-if="learnFromPrevious" class="mt-3">
        <small class="form-text text-muted d-block mb-2">
          {{ $t('Define the number of cases the Agent will use for analysis and task completion') }}
        </small>
        <label class="form-label">{{ casesCount }}</label>
        <b-form-input
          v-model="casesCount"
          type="range"
          min="0"
          max="50"
        />
        <div class="d-flex justify-content-between">
          <small>0</small>
          <small>50</small>
        </div>

        <div class="form-group mt-3">
          <label>{{ $t('Define Variables to Analyze') }}</label>
          <b-form-textarea
            v-model="variablesToAnalyze"
            rows="5"
            class="font-monospace"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    value: null,
    label: null,
    helper: null,
    property: null,
  },
  data() {
    return {
      isEnabled: false,
      selectedAgent: null,
      conditionalAssignment: '',
      actionType: 'prefill',
      variablesToFill: '',
      autonomyLevel: 50,
      learnFromPrevious: false,
      casesCount: 25,
      variablesToAnalyze: '',
      agentList: [],
      monacoOptions: {
        language: "json",
        lineNumbers: "off",
        formatOnPaste: true,
        formatOnType: true,
        automaticLayout: true,
        minimap: { enabled: false },
      },
    };
  },
  computed: {
    node() {
      return this.$root.$children[0].$refs.modeler.highlightedNode.definition;
    },
    config: {
      get() {
        return this.node.config ? JSON.parse(this.node.config) : {};
      },
      set(value) {
        this.$set(this.node, 'config', JSON.stringify(value));
      }
    }
  },
  watch: {
    'node.config': {
      immediate: true,
      handler(value) {
        if (value) {
          const config = JSON.parse(value);
          this.isEnabled = config.agentEnabled || false;
          this.selectedAgent = config.selectedAgent || null;
          this.conditionalAssignment = config.conditionalAssignment || '';
          this.actionType = config.actionType || 'prefill';
          this.variablesToFill = config.variablesToFill || this.variablesToFill;
          this.autonomyLevel = config.autonomyLevel || 50;
          this.learnFromPrevious = config.learnFromPrevious || false;
          this.casesCount = config.casesCount || 25;
          this.variablesToAnalyze = config.variablesToAnalyze || this.variablesToAnalyze;
        }
      }
    },
    isEnabled: 'updateConfig',
    selectedAgent: 'updateConfig',
    conditionalAssignment: 'updateConfig',
    actionType: 'updateConfig',
    variablesToFill: 'updateConfig',
    autonomyLevel: 'updateConfig',
    learnFromPrevious: 'updateConfig',
    casesCount: 'updateConfig',
    variablesToAnalyze: 'updateConfig'
  },
  methods: {
    setEnabled(value) {
      this.isEnabled = value;
      this.updateConfig();
    },
    setLearnFromPrevious(value) {
      this.learnFromPrevious = value;
      this.updateConfig();
    },
    setActionType(type) {
      this.actionType = type;
      this.updateConfig();
    },
    updateConfig() {
      const config = {
        agentEnabled: this.isEnabled,
        selectedAgent: this.selectedAgent,
        conditionalAssignment: this.conditionalAssignment,
        actionType: this.actionType,
        variablesToFill: this.variablesToFill,
        autonomyLevel: this.autonomyLevel,
        learnFromPrevious: this.learnFromPrevious,
        casesCount: this.casesCount,
        variablesToAnalyze: this.variablesToAnalyze
      };
      this.config = config;
    }
  },
};
</script>

<style lang="scss" scoped>
.font-monospace {
  font-family: monospace;
}

.form-group {
  margin-bottom: 1rem;
}

.form-text {
  color: #6c757d;
}

/* Estilos para los botones de acción */
.btn {
  &.active {
    background-color: #e9ecef;
    border-color: #dee2e6;
  }
}
.monaco-editor {
  height: 138px;
  border: 1px solid #cdddee;
  border-radius: 2px;
}
</style> 