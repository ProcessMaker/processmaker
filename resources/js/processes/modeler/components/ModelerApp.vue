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
          :validation-bar="validationBar"
          :show-toolbar="showToolbar"
          @validate="validationErrors = $event"
          @warnings="warnings = $event"
          @saveBpmn="emitSaveEvent"
          @discard="emitDiscardEvent"
          @close="onClose"
          @publishTemplate="publishTemplate"
          @publishPmBlock="publishPmBlock"
          @set-xml-manager="xmlManager = $event"
        />
        <pan-comment
          :commentable_id="processId"
          commentable_type="ProcessMaker\Models\Process"
          :readonly="false"
        />
      </b-card-body>
      <component
        :is="component.panel"
        v-for="(component, index) in validationBar"
        :key="`validation-status-${index}`"
        :owner="self"
      />
      <component
        :is="component.type"
        v-for="(component, index) in external"
        :key="`external-${index}`"
        :ref="`external-${component.id}`"
        :options="component.options"
      />
      <create-template-modal
        ref="create-template-modal"
        asset-type="process"
        :asset-name="processName"
        :asset-id="processId"
        :current-user-id="currentUserId"
      />
      <create-pm-block-modal
        ref="create-pm-block-modal"
        asset-type="process"
        :asset-name="processName"
        :asset-id="processId"
        :current-user-id="currentUserId"
      />
    </b-card>
  </b-container>
</template>

<script>
import { Modeler, ValidationStatus } from "@processmaker/modeler";
import CreateTemplateModal from "../../../components/templates/CreateTemplateModal.vue";
import CreatePmBlockModal from "../../../components/pm-blocks/CreatePmBlockModal.vue";
import autosaveMixins from "../../../modules/autosave/mixins";
import AssetRedirectMixin from "../../../components/shared/AssetRedirectMixin";

export default {
  name: "ModelerApp",
  components: {
    Modeler,
    ValidationStatus,
    CreateTemplateModal,
    CreatePmBlockModal,
  },
  mixins: [...autosaveMixins, AssetRedirectMixin],
  props: {
    showToolbar: {
      type: Boolean,
      default: true,
    },
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
      autoSaveDelay: window.ProcessMaker.modeler.autoSaveDelay,
      isVersionsInstalled: window.ProcessMaker.modeler.isVersionsInstalled,
      isDraft: window.ProcessMaker.modeler.isDraft,
      validationErrors: {},
      warnings: [],
      xmlManager: null,
      processName: window.ProcessMaker.modeler.process.name,
      processId: window.ProcessMaker.modeler.process.id,
      currentUserId: window.ProcessMaker.modeler.process.user_id,
    };
  },
  computed: {
    autosaveApiCall() {
      return async (generatingAssets = false) => {
        const svg = document.querySelector(".mini-paper svg");
        const css = "text { font-family: sans-serif; }";
        const style = document.createElement("style");
        style.textContent = css;
        svg.appendChild(style);
        const svgString = new XMLSerializer().serializeToString(svg);
        const xml = await this.$refs.modeler.getXmlFromDiagram();
        this.setLoadingState(true);
        try {
          const response = await ProcessMaker.apiClient.put(`/processes/${this.process.id}/draft`, {
            name: this.process.name,
            description: this.process.description,
            task_notifications: this.getTaskNotifications(),
            projects: this.process.projects,
            bpmn: xml,
            svg: svgString,
            alternative: window.ProcessMaker.AbTesting.alternative || window.ProcessMaker.modeler.draftAlternative || "A",
          });
          this.process.updated_at = response.data.updated_at;
          window.ProcessMaker.EventBus.$emit("save-changes", null, null, generatingAssets);
          this.$set(this, "warnings", response.data.warnings || []);
          if (response.data.warnings && response.data.warnings.length > 0) {
            window.ProcessMaker.EventBus.$emit("save-changes-activate-autovalidate");
          }
          // Set draft status.
          this.setVersionIndicator(true);
        } catch (error) {
          if (error.response) {
            const { message } = error.response.data;
            ProcessMaker.alert(message, "danger");
          }
        } finally {
          this.setLoadingState(false);
        }
      };
    },
    closeHref() {
      let url = "/processes";

      if (this.redirectUrl) {
        url = this.redirectUrl;
      } else if (this.process?.asset_type === "PM_BLOCK") {
        url = "/designer/pm-blocks";
      }

      return url;
    },
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

    window.ProcessMaker.EventBus.$on("modeler-save", (redirectUrl, nodeId, onSuccess, onError, generatingAssets, publishedVersion) => {
      this.saveProcess(onSuccess, onError, redirectUrl, nodeId, generatingAssets, publishedVersion);
    });
    window.ProcessMaker.EventBus.$on("modeler-change", () => {
      window.ProcessMaker.EventBus.$emit("new-changes");
      this.refreshSession();
      this.handleAutosave();
    });
    window.ProcessMaker.EventBus.$on("modeler-discard", () => {
      this.discardDraft();
    });
    // Display version indicator.
    this.setVersionIndicator();
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
        const errorsChanged = JSON.stringify(bpmnWarnings) !== JSON.stringify(this.$refs.modeler.validationErrors.bpmn)
          || JSON.stringify(bpmnWarnings) !== JSON.stringify(this.validationErrors.bpmn);
        if (errorsChanged) {
          this.$refs.modeler.$set(this.$refs.modeler.validationErrors, "bpmn", bpmnWarnings);
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
    emitSaveEvent({
      xml, svg, redirectUrl = null, nodeId = null, generatingAssets = false,
    }) {
      this.dataXmlSvg.xml = xml;
      this.dataXmlSvg.svg = svg;

      if (this.externalEmit.includes("open-modal-versions") && !generatingAssets) {
        window.ProcessMaker.EventBus.$emit("open-modal-versions", redirectUrl, nodeId);
        return;
      }

      if (this.externalEmit.includes("open-modal-versions") && generatingAssets) {
        window.ProcessMaker.EventBus.$emit("new-changes");
        this.refreshSession();
        this.handleAutosave(true, generatingAssets);
        return;
      }

      window.ProcessMaker.EventBus.$emit("modeler-save", redirectUrl, nodeId, null, null, generatingAssets);
    },
    emitDiscardEvent() {
      if (this.externalEmit.includes("open-versions-discard-modal")) {
        window.ProcessMaker.EventBus.$emit("open-versions-discard-modal");
        return;
      }
      window.ProcessMaker.EventBus.$emit("modeler-discard");
    },
    discardDraft() {
      ProcessMaker.apiClient
        .post(`/processes/${this.process.id}/close`)
        .then(() => {
          window.location.reload();
        });
    },
    saveProcess(onSuccess, onError, redirectUrl = null, nodeId = null, generatingAssets = false, publishedVersion = null) {
      const data = {
        name: this.process.name,
        description: this.process.description,
        task_notifications: this.getTaskNotifications(),
        projects: this.process.projects,
        bpmn: this.dataXmlSvg.xml || this.$refs.modeler.currentXML,
        svg: this.dataXmlSvg.svg,
        alternative: publishedVersion || window.ProcessMaker.modeler.draftAlternative || "A",
      };

      const savedSuccessfully = (response) => {
        this.process.updated_at = response.data.updated_at;
        // Now show alert
        let type = "process";
        if (this.process.is_template) {
          type = "process template";
        }

        if (!this.externalEmit.includes("open-modal-versions")) {
          ProcessMaker.alert(this.$t(`The ${type} was saved.`, { type }), "success");
        }

        // Set published status.
        this.setVersionIndicator(false);
        this.$set(this, "warnings", response.data.warnings || []);
        if (response.data.warnings && response.data.warnings.length > 0) {
          window.ProcessMaker.EventBus.$emit("save-changes-activate-autovalidate");
        }
        window.ProcessMaker.EventBus.$emit("save-changes", redirectUrl, nodeId, generatingAssets);
        if (!redirectUrl) {
          window.ProcessMaker.EventBus.$emit("redirect");
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
    setVersionIndicator(isDraft = null) {
      if (this.isVersionsInstalled) {
        this.$refs.modeler.setVersionIndicator(isDraft ?? this.isDraft);
      }
    },
    setLoadingState(isLoading = false) {
      if (this.isVersionsInstalled) {
        this.$refs.modeler.setLoadingState(isLoading);
      }
    },
    publishTemplate() {
      this.$refs["create-template-modal"].show();
    },
    publishPmBlock() {
      this.$refs["create-pm-block-modal"].show();
    },
  },
};
</script>
