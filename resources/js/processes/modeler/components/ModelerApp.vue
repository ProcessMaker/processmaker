<template>
  <b-container id="modeler-app" class="h-100 container p-0">
    <b-card no-body class="h-100">
      <b-card-header class="d-flex align-items-center header">
        <b-card-text class="m-0 font-weight-bolder">
          {{ process.name }}
        </b-card-text>

        <div class="ml-auto">
          <b-btn variant="secondary" size="sm" @click="saveBpmn">
            <i class="fas fa-save mr-1"/>
            {{ $t('Save') }}
          </b-btn>
        </div>
      </b-card-header>

      <b-card-body class="overflow-hidden position-relative">
        <modeler ref="modeler" @validate="validationErrors = $event" />
      </b-card-body>

      <b-card-footer class="p-0 border-0">
        <statusbar>
          <validation-status :validation-errors="validationErrors"/>
        </statusbar>
      </b-card-footer>
    </b-card>
  </b-container>
</template>

<script>
import { Modeler, Statusbar, ValidationStatus } from "@processmaker/spark-modeler";

export default {
  name: 'ModelerApp',
  components: {
    Modeler,
    ValidationStatus,
    Statusbar,
  },
  data() {
    return {
      process: window.ProcessMaker.modeler.process,
      validationErrors: {},
    };
  },
  methods: {
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
          })
          .catch((err) => {
            const message = err.response.data.message;
            const errors = err.response.data.errors;
            ProcessMaker.alert(message, 'danger');
            console.log(errors);
          })
        }
      });
    }
  },
  mounted() {
    ProcessMaker.$modeler = this.$refs.modeler;

    window.ProcessMaker.EventBus.$on('modeler-change', () => {
      this.refreshSession();
      window.ProcessMaker.EventBus.$emit('new-changes');
    });
  },
};
</script>

