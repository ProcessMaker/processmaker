<template>
  <b-container
    id="modeler-app"
    class="container p-0"
  >
    <b-card
      no-body
      class="h-100 border-top-0"
    >
      <b-card-body
        class="overflow-hidden position-relative p-0 vh-100"
        data-test="body-container"
      >
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
        <component
          :is="component"
          v-for="(component, index) in validationBar"
          :key="`validation-status-${index}`"
          :owner="self"
        />
      </validation-status>

      <component
        :is="component.type"
        v-for="(component, index) in external"
        :key="`external-${index}`"
        :options="component.options"
      />
    </b-card>
  </b-container>
</template>

<script>
import { Modeler, ValidationStatus } from "@processmaker/modeler";

export default {
  name: "ModelerApp",
  components: {
    Modeler,
    ValidationStatus,
  },
  data() {
    return {
      self: this,
      validationBar: [],
      external: [],
      externalEmit: [],
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
  watch: {
    validationErrors: {
      deep: true,
      handler() {
        this.updateBpmnValidations();
      },
    },
    warnings: {
      deep: true,
      handler() {
        this.updateBpmnValidations();
      },
    },
  },
  mounted() {
    ProcessMaker.$modeler = this.$refs.modeler;

    window.ProcessMaker.EventBus.$emit("modeler-app-init", this);

    window.ProcessMaker.EventBus.$on("modeler-save", (onSuccess, onError) => {
      this.saveProcess(onSuccess, onError);
    });
    window.ProcessMaker.EventBus.$on("modeler-change", () => {
      this.refreshSession();
      this.autoSaveProcess();
      window.ProcessMaker.EventBus.$emit("new-changes");
    });
  },
  methods: {
    updateBpmnValidations() {
      const { warnings } = this;
      if (warnings instanceof Array) {
        const bpmnWarnings = [];
        warnings.forEach((warning) => {
          if (warning.errors instanceof Object) {
            Object.keys(warning.errors).forEach((node) => {
              warning.errors[node].forEach((error) => {
                bpmnWarnings.push({
                  category: "error",
                  id: node,
                  message: error,
                });
              });
            });
          }
        });
        const hasErrors = JSON.stringify(bpmnWarnings) !== JSON.stringify(this.$refs.modeler.validationErrors.bpmn);
        if (hasErrors) {
          this.$refs.modeler.$set(this.$refs.modeler.validationErrors, "bpmn", bpmnWarnings);
        }
        const hasValidationErrors = JSON.stringify(bpmnWarnings) !== JSON.stringify(this.validationErrors.bpmn);
        if (hasValidationErrors) {
          this.$set(this.validationErrors, "bpmn", bpmnWarnings);
        }
      }
    },
    refreshSession: _.throttle(() => {
      ProcessMaker.apiClient({
        method: "POST",
        url: "/keep-alive",
        baseURL: "/",
      });
    }, 60000),
    runningInCypressTest() {
      return !!window.Cypress;
    },
    getTaskNotifications() {
      const notifications = {};
      this.$refs.modeler.nodes.forEach((node) => {
        const { id } = node.definition;
        if (node.notifications !== undefined) {
          notifications[id] = node.notifications;
        }
      });
      return notifications;
    },
    emitRegisteredEvents({ xml, svg }) {
      this.dataXmlSvg.xml = xml;
      this.dataXmlSvg.svg = svg;

      this.externalEmit.forEach((item) => {
        window.ProcessMaker.EventBus.$emit(item);
      });
      if (!this.externalEmit.length) {
        window.ProcessMaker.EventBus.$emit("modeler-save");
      }
    },
    saveProcess(onSuccess, onError) {
      const data = {
        name: this.process.name,
        description: this.process.description,
        task_notifications: this.getTaskNotifications(),
        bpmn: this.dataXmlSvg.xml,
        svg: this.dataXmlSvg.svg,
      };

      const savedSuccessfully = (response) => {
        this.process.updated_at = response.data.updated_at;
        // Now show alert
        ProcessMaker.alert(this.$t("The process was saved."), "success");
        window.ProcessMaker.EventBus.$emit("save-changes");
        this.$set(this, "warnings", response.data.warnings || []);
        if (response.data.warnings && response.data.warnings.length > 0) {
          this.$refs.validationStatus.autoValidate = true;
        }
        if (typeof onSuccess === "function") {
          onSuccess(response);
        }
      };

      const saveFailed = (err) => {
        const { message } = err.response.data;
        ProcessMaker.alert(message, "danger");

        if (typeof onError === "function") {
          onError(err);
        }
      };

      ProcessMaker.apiClient.put(`/processes/${this.process.id}`, data)
        .then(savedSuccessfully)
        .catch(saveFailed);
    },
    async autoSaveProcess() {
      const svg = document.querySelector(".mini-paper svg");
      const css = "text { font-family: sans-serif; }";
      const style = document.createElement("style");
      style.textContent = css;
      svg.appendChild(style);

      const svgString = new XMLSerializer().serializeToString(svg);
      const xml = await this.$refs.modeler.getXmlFromDiagram();

      const data = {
        name: this.process.name,
        description: this.process.description,
        task_notifications: this.getTaskNotifications(),
        bpmn: xml,
        svg: svgString,
        is_draft: true,
      };

      const savedSuccessfully = (response) => {
        this.process.updated_at = response.data.updated_at;
        ProcessMaker.alert(this.$t("The process was saved."), "success");
        window.ProcessMaker.EventBus.$emit("save-changes");
        this.$set(this, "warnings", response.data.warnings || []);
        if (response.data.warnings && response.data.warnings.length > 0) {
          this.$refs.validationStatus.autoValidate = true;
        }
      };

      const saveFailed = (error) => {
        const { message } = error.response.data;
        ProcessMaker.alert(message, "danger");
      };

      ProcessMaker.apiClient.put(`/processes/${this.process.id}`, data)
        .then(savedSuccessfully)
        .catch(saveFailed);
    },
  },
};
</script>
