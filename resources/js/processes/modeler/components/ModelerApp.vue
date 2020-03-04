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
          @saveBpmn="saveBpmn"
        />
      </b-card-body>

      <validation-status
        ref="validationStatus"
        :validation-errors="validationErrors"
        :warnings="warnings"
        :owner="self"
      >
        <component v-for="(component, index) in validationBar" :key="`validation-status-${index}`" :is="component" :owner="self" />
      </validation-status>
    </b-card>
  </b-container>
</template>

<script>
import { Modeler, ValidationStatus } from "@processmaker/modeler";

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
      decorations: {
        borderOutline: {},
      },
      process: window.ProcessMaker.modeler.process,
      validationErrors: {},
      warnings: [],
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
    saveBpmn() {
      this.$refs.modeler.toXML((err, xml) => {
        if(err) {
          ProcessMaker.alert("There was an error saving: " + err, 'danger');
        } else {
          // lets save
          // Call process update
          var data = {
            name: this.process.name,
            description: this.process.description,
            task_notifications: this.getTaskNotifications(),
            bpmn: xml
          };
          ProcessMaker.apiClient.put('/processes/' + this.process.id, data)
          .then((response) => {
            this.process.updated_at = response.data.updated_at;
            // Now show alert
            ProcessMaker.alert(this.$t('The process was saved.'), 'success');
            window.ProcessMaker.EventBus.$emit('save-changes');
            this.$set(this, 'warnings', response.data.warnings || []);
            if (response.data.warnings && response.data.warnings.length > 0) {
              this.$refs.validationStatus.autoValidate = true;
            }
          })
          .catch((err) => {
            const message = err.response.data.message;
            const errors = err.response.data.errors;
            ProcessMaker.alert(message, 'danger');
          })
        }
      });
    }
  },
  mounted() {
    ProcessMaker.$modeler = this.$refs.modeler;

    window.ProcessMaker.EventBus.$on('modeler-save', () => {
      this.saveBpmn();
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
