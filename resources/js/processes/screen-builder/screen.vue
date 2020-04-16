<template>
  <div class="h-100">
    <b-card no-body class="h-100 bg-white border-top-0" id="app">
      <!-- Card Header -->
      <top-menu ref="menuScreen" :options="optionsMenu" :environment="self" />

      <!-- Card Body -->
      <b-card-body class="overflow-auto p-0 h-100" id="screen-builder-container">
        <!-- Vue-form-builder -->
        <vue-form-builder
          class="m-0"
          :validationErrors="validationErrors"
          :initialConfig="screen.config"
          :title="screen.title"
          :class="displayBuilder ? 'd-flex' : 'd-none'"
          :screenType="type"
          ref="builder"
          @change="updateConfig"
          :screen="screen"
        />

        <!-- Preview -->
        <b-row class="h-100 m-0" id="preview" v-show="displayPreview">
          <b-col class="overflow-auto h-100">
            <vue-form-renderer
              ref="renderer"
              v-model="previewData"
              class="p-3"
              @submit="previewSubmit"
              @update="onUpdate"
              :mode="mode"
              :config="config"
              :computed="computed"
              :custom-css="customCSS"
              :watchers="watchers"
              v-on:css-errors="cssErrors = $event"
              :mock-magic-variables="mockMagicVariables"
            />
          </b-col>

          <b-col class="overflow-hidden h-100 preview-inspector p-0">
            <b-card no-body class="p-0 h-100 rounded-0 border-top-0 border-right-0 border-bottom-0">
              <b-card-body class="p-0 overflow-auto">
                <div v-for="(component, index) in previewComponents" :key="index">
                  <component :is="component" :data="previewData" @input="previewData = $event"></component>
                </div>

                <b-button
                  variant="outline"
                  class="text-left card-header d-flex align-items-center w-100 shadow-none text-capitalize"
                  @click="showDataInput = !showDataInput"
                >
                  <i class="fas fa-file-import mr-2"></i>
                  {{ $t('Data Input') }}
                  <i
                    class="fas ml-auto"
                    :class="showDataInput ? 'fa-angle-right' : 'fa-angle-down'"
                  ></i>
                </b-button>

                <b-collapse v-model="showDataInput" id="showDataInput">
                  <monaco-editor
                    :options="monacoOptions"
                    class="data-collapse"
                    v-model="previewInput"
                    language="json"
                  />

                  <div v-if="!previewInputValid" class="pl-3">
                    <i class="fas text-danger fa-times-circle mr-1"></i>
                    <small class="text-muted text-capitalize">{{ $t('Invalid JSON Data Object') }}</small>
                  </div>
                </b-collapse>

                <b-button
                  variant="outline"
                  class="text-left card-header d-flex align-items-center w-100 shadow-none text-capitalize"
                  data-toggle="collapse"
                  @click="showDataPreview = !showDataPreview"
                >
                  <i class="fas fa-file-code mr-2"></i>
                  {{ $t('Data Preview') }}
                  <i
                    class="fas ml-auto"
                    :class="showDataPreview ? 'fa-angle-right' : 'fa-angle-down'"
                  ></i>
                </b-button>

                <b-collapse v-model="showDataPreview" id="showDataPreview" class="mt-2">
                  <vue-json-pretty :data="previewData" class="p-2 data-collapse"></vue-json-pretty>
                </b-collapse>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-card-body>

      <!-- Card Footer -->
      <b-card-footer class="d-flex d-flex justify-content-end align-items-center">
        <b-form-checkbox
          v-model="toggleValidation"
          name="check-button"
          switch
        >{{ $t('Screen Validation') }}</b-form-checkbox>

        <div class="ml-3" @click="showValidationErrors = !showValidationErrors">
          <button type="button" class="btn btn-sm text-capitalize">
            <i class="fas fa-angle-double-up"></i>
            {{ $t('Open Console') }}
            <span
              v-if="allErrors === 0"
              class="badge badge-success"
            >
              <i class="fas fa-check-circle"></i>
              {{ $t(allErrors) }}
            </span>

            <span v-else class="badge badge-danger">
              <i class="fas fa-times-circle"></i>
              {{ $t(allErrors) }}
            </span>
          </button>
        </div>

        <div
          v-if="showValidationErrors"
          class="validation-panel position-absolute border-top border-left overflow-auto"
          :class="{'d-block':showValidationErrors && validationErrors.length}"
        >
          <b-button
            variant="link"
            class="validation__message d-flex align-items-center p-3 text-capitalize"
            v-for="(validation,index) in validationErrors"
            :key="index"
            @click="focusInspector(validation)"
          >
            <i class="fas fa-times-circle text-danger d-block mr-3"></i>
            <span class="ml-2 text-dark font-weight-bold text-left">
              {{ validation.item && validation.item.component }}
              <span
                class="d-block font-weight-normal"
              >{{ validation.message }}</span>
            </span>
          </b-button>
          <span
            v-if="!allErrors"
            class="d-flex justify-content-center align-items-center h-100 text-capitalize"
          >{{ $t('No Errors') }}</span>
        </div>
      </b-card-footer>
    </b-card>
    <!-- Modals -->
    <computed-properties v-model="computed" ref="computedProperties"></computed-properties>
    <custom-CSS v-model="customCSS" ref="customCSS" :cssErrors="cssErrors" />
    <watchers-popup v-model="watchers" ref="watchersPopup" />
  </div>
</template>

<script>
import { VueFormBuilder, VueFormRenderer } from "@processmaker/screen-builder";
import WatchersPopup from "@processmaker/screen-builder/src/components/watchers-popup.vue";
import ComputedProperties from "@processmaker/screen-builder/src/components/computed-properties";
import CustomCSS from "@processmaker/screen-builder/src/components/custom-css";
import "@processmaker/screen-builder/dist/vue-form-builder.css";
import "@processmaker/vue-form-elements/dist/vue-form-elements.css";
import VueJsonPretty from "vue-json-pretty";
import MonacoEditor from "vue-monaco";
import mockMagicVariables from "./mockMagicVariables";
import TopMenu from "../../components/Menu";

// Bring in our initial set of controls
import globalProperties from "@processmaker/screen-builder/src/global-properties";
import _ from "lodash";

import Validator from "validatorjs";
import formTypes from "./formTypes";

// To include another language in the Validator with variable processmaker
if (
  window.ProcessMaker &&
  window.ProcessMaker.user &&
  window.ProcessMaker.user.lang
) {
  Validator.useLang(window.ProcessMaker.user.lang);
}

Validator.register(
  "attr-value",
  value => {
    return value.match(/^[a-zA-Z0-9-_]+$/);
  },
  "Must be letters, numbers, underscores or dashes"
);

export default {
  props: ["process", "screen", "permission"],
  data() {
    const defaultConfig = [
      {
        name: "Default",
        computed: [],
        items: []
      }
    ];

    const options = [
      {
        id: "group_design",
        type: "group",
        section: "left",
        items: [
          {
            id: "button_design",
            type: "button",
            title: this.$t("Design Screen"),
            name: this.$t("Design"),
            variant: "secondary",
            icon: "fas fa-drafting-compass pr-1",
            action: 'changeMode("editor")'
          },
          {
            id: "button_preview",
            type: "button",
            title: this.$t("Preview Screen"),
            name: this.$t("Preview"),
            variant: "outline-secondary",
            icon: "fas fa-cogs pr-1",
            action: 'changeMode("preview")'
          }
        ]
      },
      {
        id: "group_properties",
        type: "group",
        section: "right",
        items: [
          {
            id: "button_calcs",
            type: "button",
            title: this.$t("Calculated Properties"),
            name: this.$t("Calcs"),
            variant: "secondary",
            icon: "fas fa-flask",
            action: 'openComputedProperties()'
          },
          {
            id: "button_custom_css",
            type: "button",
            title: this.$t("Custom CSS"),
            name: this.$t("CSS"),
            variant: "secondary",
            icon: "fab fa-css3",
            action: 'openCustomCSS()'
          },
          {
            id: "button_watchers",
            type: "button",
            title: this.$t("Watchers"),
            name: this.$t("Watchers"),
            variant: "secondary",
            icon: "fas fa-mask",
            action: 'openWatchersPopup()'
          }
        ]
      },
      {
        id: "button_export",
        section: "right",
        type: "button",
        title: this.$t("Export Screen"),
        name: "",
        variant: "secondary",
        icon: "fas fa-file-export",
        action: 'beforeExportScreen()'
      },
      {
        id: "button_save",
        section: "right",
        type: "button",
        title: this.$t("Save Screen"),
        name: "",
        variant: "secondary",
        icon: "fas fa-save",
        action: () => {
          ProcessMaker.EventBus.$emit("save-screen", false);
        }
      }
    ];

    return {
      self: this,
      watchers_config: {
        api: {
          scripts: [],
          execute: null
        }
      },
      type: formTypes.form,
      mode: "editor",
      // Computed properties
      computed: [],
      // Watchers
      watchers: [],
      config: this.screen.config || defaultConfig,
      previewData: {},
      previewInput: "{}",
      customCSS: "",
      cssErrors: "",
      showValidationErrors: false,
      toggleValidation: true,
      showDataPreview: true,
      showDataInput: true,
      monacoOptions: {
        automaticLayout: true,
        lineNumbers: "off",
        minimap: false
      },
      mockMagicVariables,
      validationWarnings: [],
      previewComponents: [],
      optionsMenu: options
    };
  },
  components: {
    VueFormBuilder,
    VueFormRenderer,
    VueJsonPretty,
    ComputedProperties,
    CustomCSS,
    WatchersPopup,
    MonacoEditor,
    TopMenu
  },
  watch: {
    mode(mode) {
      if (mode === "preview") {
        this.previewData = this.previewInput
          ? JSON.parse(this.previewInput)
          : null;
      }
    },
    config() {
      // Reset the preview data with clean object to start
      this.previewData = {};
    },
    previewInput() {
      if (this.previewInputValid) {
        // Copy data over
        this.previewData = JSON.parse(this.previewInput);
      } else {
        this.previewData = {};
      }
    }
  },
  computed: {
    previewInputValid() {
      try {
        JSON.parse(this.previewInput);
        return true;
      } catch (err) {
        return false;
      }
    },
    displayBuilder() {
      return this.mode === "editor";
    },
    displayPreview() {
      return this.mode === "preview";
    },
    allErrors() {
      return this.validationErrors.length;
    },
    validationErrors() {
      if (!this.toggleValidation) {
        return [];
      }

      const validationErrors = [];
      this.validationWarnings.splice(0);

      if (this.type === formTypes.form && !this.containsSubmitButton()) {
        this.validationWarnings.push(
          "Warning: Screens without save buttons cannot be executed."
        );
      }

      this.config.forEach(page => {
        validationErrors.push(
          ...this.getValidationErrorsForItems(page.items, page)
        );
      });

      return validationErrors;
    }
  },
  mounted() {
    // Call our init lifecycle event
    ProcessMaker.EventBus.$emit("screen-builder-init", this);
    this.computed = this.screen.computed ? this.screen.computed : [];
    this.customCSS = this.screen.custom_css ? this.screen.custom_css : "";
    this.watchers = this.screen.watchers ? this.screen.watchers : [];
    this.updatePreview(new Object());
    this.previewInput = "{}";

    ProcessMaker.EventBus.$emit("screen-builder-start", this);
    ProcessMaker.EventBus.$on("save-screen", (value, onSuccess, onError) => {
      this.saveScreen(value, onSuccess, onError);
    });
  },
  methods: {
    changeMode(mode) {
      if (mode === "editor") {
        this.$refs.menuScreen.changeItem("button_design", {
          variant: "secondary"
        });
        this.$refs.menuScreen.changeItem("button_preview", {
          variant: "outline-secondary"
        });
        this.$refs.menuScreen.sectionRight = true;
      }
      if (mode === "preview") {
        this.$refs.menuScreen.changeItem("button_design", {
          variant: "outline-secondary"
        });
        this.$refs.menuScreen.changeItem("button_preview", {
          variant: "secondary"
        });
        this.$refs.menuScreen.sectionRight = false;
      }
      this.mode = mode;
    },
    onUpdate(data) {
      ProcessMaker.EventBus.$emit("form-data-updated", data);
    },
    getValidationErrorsForItems(items, page) {
      const validationErrors = [];

      if (!Array.isArray(items)) {
        items = [items];
      }

      items.forEach(item => {
        if (item.container) {
          item.items.forEach(containerItems => {
            validationErrors.push(
              ...this.getValidationErrorsForItems(containerItems, page)
            );
          });
        }

        const data = item.config || {};
        const rules = {};

        item.inspector.forEach(property => {
          if (property.config.validation) {
            rules[property.field] = property.config.validation;
          }
        });

        const validator = new Validator(data, rules);

        // Validation will not run until you call passes/fails on it
        if (!validator.passes()) {
          Object.keys(validator.errors.errors).forEach(field => {
            validator.errors.errors[field].forEach(error => {
              validationErrors.push({
                message: error,
                page,
                item
              });
            });
          });
        }
      });

      return validationErrors;
    },
    containsSubmitButton() {
      return this.config.some(config => {
        return this.itemsContainSubmitButton(config.items);
      });
    },
    isSubmitButton(item) {
      return item.component === "FormButton" && item.config.event === "submit";
    },
    itemsContainSubmitButton(items) {
      if (!Array.isArray(items)) {
        items = [items];
      }
      return items.some(item => {
        return item.container
          ? item.items.some(this.itemsContainSubmitButton)
          : this.isSubmitButton(item);
      });
    },
    beforeExportScreen() {
      this.saveScreen(true);
    },
    focusInspector(validate) {
      if (!validate.item || !validate.page) {
        return;
      }

      this.$refs.builder.focusInspector(validate);
    },
    openWatchersPopup() {
      this.$refs.watchersPopup.show();
    },
    openComputedProperties() {
      this.$refs.computedProperties.show();
    },
    openCustomCSS() {
      this.$refs.customCSS.show();
    },
    updateConfig(newConfig) {
      this.config = newConfig;
      this.refreshSession();
      ProcessMaker.EventBus.$emit("new-changes");
    },
    updatePreview(data) {
      this.previewData = data;
    },
    previewSubmit() {
      alert("Preview Form was Submitted");
    },
    addControl(
      control,
      rendererComponent,
      rendererBinding,
      builderComponent,
      builderBinding
    ) {
      // Add it to the renderer
      this.$refs.renderer.$options.components[
        rendererBinding
      ] = rendererComponent;
      // Add it to the form builder
      this.$refs.builder.addControl(control, builderComponent, builderBinding);
    },
    addPreviewComponent(component) {
      this.previewComponents.push(component);
    },
    refreshSession: _.throttle(function() {
      ProcessMaker.apiClient({
        method: "POST",
        url: "/keep-alive",
        baseURL: "/"
      });
    }, 60000),
    onClose() {
      window.location.href = "/designer/screens";
    },
    beforeExportScreen() {
      this.saveScreen(true);
    },
    exportScreen() {
      ProcessMaker.apiClient
        .post("screens/" + this.screen.id + "/export")
        .then(response => {
          window.open(response.data.url);
          ProcessMaker.alert(this.$t("The screen was exported."), "success");
        })
        .catch(error => {
          ProcessMaker.alert(error.response.data.error, "danger");
        });
    },
    saveScreen(exportScreen, onSuccess, onError) {
      if (this.allErrors !== 0) {
        ProcessMaker.alert(
          this.$t("This screen has validation errors."),
          "danger"
        );
      } else {
        if (this.validationWarnings.length > 0) {
          this.validationWarnings.forEach(warning =>
            ProcessMaker.alert(warning, "warning")
          );
        }
        ProcessMaker.apiClient
          .put("screens/" + this.screen.id, {
            title: this.screen.title,
            description: this.screen.description,
            type: this.screen.type,
            config: this.config,
            computed: this.computed,
            custom_css: this.customCSS,
            watchers: this.watchers
          })
          .then(response => {
            if (exportScreen) {
              this.exportScreen();
            }
            ProcessMaker.alert(this.$t("Successfully saved"), "success");
            ProcessMaker.EventBus.$emit("save-changes");
            if (typeof onSuccess === "function") {
              onSuccess(response);
            }
          })
          .catch(err => {
            if (typeof onError === "function") {
              onError(err);
            }
          });
      }
    }
  }
};
</script>

<style lang="scss">
    $validation-panel-bottom: 3.5rem;
    $validation-panel-right: 0;
    $validation-panel-height: 10rem;
    $validation-panel-width: 23rem;
    $primary-white: #f7f7f7;

    html,
    body {
        height: 100%;
        min-height: 100%;
        max-height: 100%;
        overflow: hidden;
    }

    .header-bg {
      background: #f7f7f7;
    }

    .validation-panel {
      background: $primary-white;
      height: $validation-panel-height;
      width: $validation-panel-width;
      bottom: $validation-panel-bottom;
      right: $validation-panel-right;
    }

    .preview-inspector {
      max-width: 265px;
    }

    .data-collapse {
      height: 225px;
    }
</style>
