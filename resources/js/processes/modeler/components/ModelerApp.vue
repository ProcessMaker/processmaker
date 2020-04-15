<template>
  <b-container id="modeler-app" class="container p-0">
    <b-card no-body class="h-100 border-top-0">
      <b-card-body class="overflow-hidden position-relative p-0 vh-100" data-test="body-container">
        <modeler
          ref="modeler"
          :owner="self"
          :decorations="decorations"
          @validate="validationErrors = $event"
          @warnings="warnings = $event"
          @saveBpmn="emitRegisteredEvents"
          @set-xml-manager="xmlManager = $event"
        />
      </b-card-body>

      <validation-status
        ref="validationStatus"
        :validation-errors="validationErrors"
        :warnings="warnings"
        :owner="self"
        :xml-manager="xmlManager"
      >
        <component v-for="(component, index) in validationBar" :key="`validation-status-${index}`" :is="component" :owner="self" />
      </validation-status>

      <component v-for="(component, index) in external" :key="`external-${index}`" :is="component.type" :options="component.options"/>

    </b-card>
  </b-container>
</template>

<script>
  import {Modeler, ValidationStatus} from "@processmaker/modeler";

  export default {
  name: 'ModelerApp',
  components: {
    Modeler,
    ValidationStatus,
  },
  data() {
    return {
      self: this,
      validationBar: [],
      external: [],
      externalEmit : [],
      dataXmlSvg: {},
      decorations: {
        borderOutline: {},
      },
      process: window.ProcessMaker.modeler.process,
      validationErrors: {},
      warnings: [],
      xmlManager: null,
    };
  },
  methods: {
    updateBpmnValidations() {
      const statusBar = this.$refs.validationStatus;
      const warnings = this.warnings;
      if(warnings instanceof Array) {
        const bpmnWarnings = [];
        warnings.forEach((warning) => {
          if (warning.errors instanceof Object) {
            Object.keys(warning.errors).forEach(node => {
              warning.errors[node].forEach(error => {
                bpmnWarnings.push({
                  category: 'error',
                  id: node,
                  message: error
                });
              });
            });
          }
        });
        JSON.stringify(bpmnWarnings) !== JSON.stringify(this.$refs.modeler.validationErrors.bpmn)
          ? this.$refs.modeler.$set(this.$refs.modeler.validationErrors, 'bpmn', bpmnWarnings) : null;
        JSON.stringify(bpmnWarnings) !== JSON.stringify(this.validationErrors.bpmn)
          ? this.$set(this.validationErrors, 'bpmn', bpmnWarnings) : null;
      }
    },
    refreshSession: _.throttle(() => {
      ProcessMaker.apiClient({
        method: 'POST',
        url: '/keep-alive',
        baseURL: '/',
      })
    }, 60000),
    runningInCypressTest() {
      return !!window.Cypress;
    },
    getTaskNotifications() {
      var notifications = {};
      this.$refs.modeler.nodes.forEach(function(node) {
        let id = node.definition.id;
        if (node.notifications !== undefined) {
          notifications[id] = node.notifications;
        }
      });
      return notifications;
    },
    emitRegisteredEvents ({xml, svg}) {
      this.dataXmlSvg.xml = xml;
      this.dataXmlSvg.svg = svg;

      this.externalEmit.forEach(item => {
        window.ProcessMaker.EventBus.$emit(item);
      })
      if (!this.externalEmit.length) {
        window.ProcessMaker.EventBus.$emit('modeler-save');
      }
    },
    saveProcess(onSuccess, onError) {
      const data = {
        name: this.process.name,
        description: this.process.description,
        task_notifications: this.getTaskNotifications(),
        bpmn: this.dataXmlSvg.xml,
        svg: this.dataXmlSvg.svg
      };

      const savedSuccessfully = (response) => {
        this.process.updated_at = response.data.updated_at;
        // Now show alert
        ProcessMaker.alert(this.$t('The process was saved.'), 'success');
        window.ProcessMaker.EventBus.$emit('save-changes');
        this.$set(this, 'warnings', response.data.warnings || []);
        if (response.data.warnings && response.data.warnings.length > 0) {
          this.$refs.validationStatus.autoValidate = true;
        }
        if (typeof onSuccess === 'function') {
          onSuccess(response);
        }
      };

      const saveFailed = (err) => {
        const message = err.response.data.message;
        const errors = err.response.data.errors;
        ProcessMaker.alert(message, 'danger');

        if (typeof onError === 'function') {
          onError(err);
        }
      };

      ProcessMaker.apiClient.put('/processes/' + this.process.id, data)
              .then(savedSuccessfully)
              .catch(saveFailed);
    }
  },
  mounted() {
    ProcessMaker.$modeler = this.$refs.modeler;

    window.ProcessMaker.EventBus.$emit('modeler-app-init', this);

    window.ProcessMaker.EventBus.$on('modeler-save', (onSuccess, onError) => {
      this.saveProcess(onSuccess, onError);
    });
    window.ProcessMaker.EventBus.$on('modeler-change', () => {
      this.refreshSession();
      window.ProcessMaker.EventBus.$emit('new-changes');
    });
  },
  watch: {
    validationErrors: {
      deep: true,
      handler() {
        this.updateBpmnValidations();
      }
    },
    warnings: {
      deep: true,
      handler() {
        this.updateBpmnValidations();
      }
    }
  }
};
</script>
