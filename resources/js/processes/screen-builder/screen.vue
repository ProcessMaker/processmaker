<template>
  <div class="h-100">
    <b-card
      id="app"
      no-body
      class="h-100 bg-white border-top-0"
    >
      <!-- Card Header -->
      <top-menu
        ref="menuScreen"
        :options="optionsMenu"
        :environment="self"
        @translate="translateScreen"
      />

      <!-- Card Body -->
      <b-card-body
        id="screen-builder-container"
        class="overflow-auto p-0 h-100"
      >
        <!-- Vue-form-builder -->
        <vue-form-builder
          ref="builder"
          class="m-0"
          :validation-errors="validationErrors"
          :initial-config="screen.config"
          :title="screen.title"
          :class="displayBuilder ? 'd-flex' : 'd-none'"
          :screen-type="type"
          :screen="screen"
          :render-controls="displayBuilder"
          :process-id="processId"
          @change="updateConfig"
        >
          <data-loading-basic :is-loaded="false" />
        </vue-form-builder>

        <!-- Preview -->
        <b-row
          v-show="displayPreview"
          id="preview"
          class="h-100 m-0"
        >
          <b-col class="d-flex overflow-auto h-100">
            <vue-form-renderer
              v-if="renderComponent === 'task-screen'"
              ref="renderer"
              :key="rendererKey"
              v-model="previewData"
              class="p-3"
              :mode="mode"
              :config="preview.config"
              :computed="preview.computed"
              :custom-css="preview.custom_css"
              :watchers="preview.watchers"
              :show-errors="true"
              :mock-magic-variables="mockMagicVariables"
              :device-screen="deviceScreen"
              @submit="previewSubmit"
              @update="onUpdate"
              @css-errors="cssErrors = $event"
            />
            <div
              v-else
              :class="{
                'device-mobile': deviceScreen === 'mobile',
                'device-screen': deviceScreen !== 'mobile',
              }"
            >
              <component
                :is="renderComponent"
                v-model="previewData"
                :mode="mode"
                :screen="preview.config"
                :computed="preview.computed"
                :custom-css="preview.custom_css"
                :watchers="preview.watchers"
                :data="previewData"
                :type="screen.type"
                @update="onUpdate"
                @submit="previewSubmit"
              />
            </div>
          </b-col>

          <b-col class="overflow-hidden h-100 preview-inspector p-0">
            <b-card
              no-body
              class="p-0 h-100 rounded-0 border-top-0 border-right-0 border-bottom-0"
            >
              <b-card-body class="p-0 overflow-auto">
                <div
                  v-for="(component, index) in previewComponents"
                  :key="index"
                >
                  <component
                    :is="component"
                    :data="previewData"
                    @input="previewData = $event"
                  />
                </div>

                <b-button
                  variant="outline"
                  class="text-left card-header d-flex align-items-center w-100 shadow-none text-capitalize"
                  @click="showDataInput = !showDataInput"
                >
                  <i class="fas fa-file-import mr-2" />
                  {{ $t("Data Input") }}
                  <i
                    class="fas ml-auto"
                    :class="showDataInput ? 'fa-angle-right' : 'fa-angle-down'"
                  />
                </b-button>

                <b-collapse
                  id="showDataInput"
                  v-model="showDataInput"
                >
                  <monaco-editor
                    v-model="previewInput"
                    :options="monacoOptions"
                    class="data-collapse"
                    language="json"
                    @change="updateDataInput"
                  />

                  <div
                    v-if="!previewInputValid"
                    class="pl-3"
                  >
                    <i class="fas text-danger fa-times-circle mr-1" />
                    <small class="text-muted text-capitalize">{{
                      $t("Invalid JSON Data Object")
                    }}</small>
                  </div>
                </b-collapse>

                <b-button
                  variant="outline"
                  class="text-left card-header d-flex align-items-center w-100 shadow-none text-capitalize"
                  data-toggle="collapse"
                  @click="showDataPreview = !showDataPreview"
                >
                  <i class="fas fa-file-code mr-2" />
                  {{ $t("Data Preview") }}
                  <b-button
                    v-b-modal.data-preview
                    squared
                    variant="outline-dark"
                    class="fas ml-auto btn-sm tree-button"
                    @click.stop
                  >
                    <i class="fas ml-auto fas fa-expand" />
                  </b-button>
                  <i
                    class="fas ml-auto"
                    :class="
                      showDataPreview ? 'fa-angle-right' : 'fa-angle-down'
                    "
                  />
                </b-button>

                <b-collapse
                  id="showDataPreview"
                  v-model="showDataPreview"
                  class="mt-2"
                >
                  <monaco-editor
                    v-model="previewDataStringify"
                    :options="monacoOptions"
                    class="editor"
                    language="json"
                    @editorDidMount="monacoMounted"
                  />
                </b-collapse>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-card-body>

      <!-- Card Footer -->
      <b-card-footer
        class="d-flex d-flex justify-content-end align-items-center"
      >
        <b-form-checkbox
          v-model="toggleValidation"
          name="check-button"
          switch
        >
          {{ $t("Screen Validation") }}
        </b-form-checkbox>

        <div
          class="ml-3"
          @click="showValidationErrors = !showValidationErrors"
        >
          <button
            type="button"
            class="btn btn-sm text-capitalize"
          >
            <i class="fas fa-angle-double-up" />
            {{ $t("Open Console") }}
            <span
              v-if="allErrors === 0 && allWarnings === 0"
              class="badge badge-success"
            >
              <i class="fas fa-check-circle" />
            </span>

            <span
              v-if="allErrors > 0"
              class="badge badge-danger"
            >
              <i class="fas fa-times-circle" />
              {{ $t(allErrors) }}
            </span>
            <span
              v-if="allWarnings > 0"
              class="badge badge-warning"
            >
              <i class="fas fa-exclamation-triangle" />
              {{ $t(allWarnings) }}
            </span>
          </button>
        </div>

        <div
          v-if="showValidationErrors"
          class="validation-panel position-absolute border-top border-left overflow-auto"
          :class="{
            'd-block': showValidationErrors && validationErrors.length,
          }"
        >
          <b-button
            v-for="(validation, index) in warnings"
            :key="index"
            variant="link"
            class="validation__message d-flex align-items-center p-3"
            @click="focusInspector(validation)"
          >
            <i class="fas fa-exclamation-triangle text-warning d-block mr-3" />
            <span class="ml-2 text-dark font-weight-bold text-left">
              {{ validation.reference }}
              <span class="d-block font-weight-normal">{{
                validation.message
              }}</span>
            </span>
          </b-button>
          <b-button
            v-for="(validation, index) in validationErrors"
            :key="index"
            variant="link"
            class="validation__message d-flex align-items-center p-3 text-capitalize"
            @click="focusInspector(validation)"
          >
            <i class="fas fa-times-circle text-danger d-block mr-3" />
            <span class="ml-2 text-dark font-weight-bold text-left">
              {{ validation.item && validation.item.component }}
              <span class="d-block font-weight-normal">{{
                validation.message
              }}</span>
            </span>
          </b-button>
          <span
            v-if="!allErrors && !allWarnings"
            class="d-flex justify-content-center align-items-center h-100 text-capitalize"
          >{{ $t("No Errors") }}</span>
        </div>
      </b-card-footer>
    </b-card>
    <!-- Modals -->
    <b-modal
      id="data-preview"
      hide-footer
      size="xl"
      title="Output Preview Panel"
      header-close-content="&times;"
    >
      <b-row class="h-100">
        <b-col cols="6">
          <monaco-editor
            v-model="previewDataStringify"
            :options="monacoOptions"
            class="editor"
            language="json"
            @editorDidMount="monacoMounted"
          />
        </b-col>
        <b-col cols="6">
          <tree-view
            v-model="previewDataStringify"
            :iframe-height="iframeHeight"
            style="border: 1px solid gray;"
          />
        </b-col>
      </b-row>
    </b-modal>
    <computed-properties
      ref="computedProperties"
      v-model="computed"
      @input="onInput()"
    />
    <custom-CSS
      ref="customCSS"
      v-model="customCSS"
      :css-errors="cssErrors"
      @input="onInput()"
    />
    <watchers-popup
      ref="watchersPopup"
      v-model="watchers"
      @input="onInput()"
    />
    <create-template-modal
      id="create-template-modal"
      ref="create-template-modal"
      asset-type="screen"
      :current-user-id="user.id"
      :asset-name="screen.title"
      :asset-id="screen.id"
      :screen-type="screen.type"
      :permission="permission"
      :types="{ [screen.type]: screen.type }"
      header-class="border-0"
      footer-class="border-0"
      modal-size="lg"
    />
  </div>
</template>

<script>
import {
  VueFormBuilder,
  VueFormRenderer,
  WatchersPopup,
  ComputedProperties,
  CustomCSS,
} from "@processmaker/screen-builder";
import "@processmaker/vue-form-elements/dist/vue-form-elements.css";
import MonacoEditor from "vue-monaco";
import _, { cloneDeep, debounce } from "lodash";
import { mapMutations } from "vuex";
import Validator from "@chantouchsek/validatorjs";
import TopMenu from "../../components/Menu.vue";
import mockMagicVariables from "./mockMagicVariables";
import formTypes from "./formTypes";
import DataLoadingBasic from "../../components/shared/DataLoadingBasic.vue";
import AssetRedirectMixin from "../../components/shared/AssetRedirectMixin";
import autosaveMixins from "../../modules/autosave/mixins";
import CreateTemplateModal from "../../components/templates/CreateTemplateModal.vue";

export default {
  components: {
    VueFormBuilder,
    VueFormRenderer,
    ComputedProperties,
    CustomCSS,
    WatchersPopup,
    MonacoEditor,
    TopMenu,
    DataLoadingBasic,
    CreateTemplateModal,
  },
  mixins: [...autosaveMixins, AssetRedirectMixin],
  props: {
    screen: {
      type: Object,
      required: true,
    },
    process: {
      type: Object,
      default: () => {},
    },
    permission: {
      type: Array,
      default: () => [],
    },
    autoSaveDelay: {
      type: Number,
      default: 5000,
    },
    isVersionsInstalled: {
      type: Boolean,
      default: false,
    },
    isDraft: {
      type: Boolean,
      default: false,
    },
    processId: {
      type: Number,
      default: 0,
    },
  },
  data() {
    const defaultConfig = [
      {
        name: "Default",
        computed: [],
        items: [],
      },
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
            action: "changeMode(\"editor\")",
          },
          {
            id: "button_preview",
            type: "button",
            title: this.$t("Preview Screen"),
            name: this.$t("Preview"),
            variant: "outline-secondary",
            icon: "fas fa-cogs pr-1",
            action: "changeMode(\"preview\")",
          },
        ],
      },
      {
        id: "group_preview",
        type: "group",
        section: "left",
        displayCondition: "displayPreview",
        items: [
          {
            id: "button_preview_desktop",
            type: "button",
            title: this.$t("Preview Desktop"),
            variant: "secondary",
            icon: "fas fa-desktop",
            action: "changeDeviceScreen(\"desktop\")",
          },
          {
            id: "button_preview_mobile",
            type: "button",
            title: this.$t("Preview Mobile"),
            variant: "outline-secondary",
            icon: "fas fa-mobile pr-1",
            action: "changeDeviceScreen(\"mobile\")",
          },
        ],
      },
      {
        id: "group_properties",
        type: "group",
        section: "right",
        items: [
          {
            id: "undo",
            type: "button",
            title: this.$t("Undo"),
            name: this.$t("Undo"),
            variant: "link",
            icon: "fas fa-undo",
            action: "undoAction()",
          },
          {
            id: "redo",
            type: "button",
            title: this.$t("Redo"),
            name: this.$t("Redo"),
            variant: "link",
            icon: "fas fa-redo",
            action: "redoAction()",
          },
          {
            id: "button_calcs",
            type: "button",
            title: this.$t("Calculated Properties"),
            name: this.$t("Calcs"),
            variant: "link",
            icon: "fas fa-flask",
            action: "openComputedProperties()",
          },
          {
            id: "button_custom_css",
            type: "button",
            title: this.$t("Custom CSS"),
            name: this.$t("CSS"),
            variant: "link",
            icon: "fab fa-css3",
            action: "openCustomCSS()",
          },
          {
            id: "button_watchers",
            type: "button",
            title: this.$t("Watchers"),
            name: this.$t("Watchers"),
            variant: "link",
            icon: "fas fa-mask",
            action: "openWatchersPopup()",
          },
        ],
      },
      {
        id: "button_save",
        section: "right",
        type: "button",
        title: this.$t("Save Screen"),
        name: "",
        variant: "link",
        icon: "fas fa-save",
        action: () => {
          ProcessMaker.EventBus.$emit("save-screen", false);
        },
      },
    ];

    return {
      user: {},
      previewDataStringify: "",
      numberOfElements: 0,
      preview: {
        config: [
          {
            name: "Default",
            computed: [],
            items: [],
          },
        ],
        computed: [],
        custom_css: "",
        watchers: [],
      },
      self: this,
      watchers_config: {
        api: {
          scripts: [],
          execute: null,
        },
      },
      type: formTypes.form,
      mode: "editor",
      deviceScreen: "desktop",
      // Computed properties
      computed: [],
      // Watchers
      watchers: [],
      config: this.screen.config || defaultConfig,
      previewData: {},
      previewDataSaved: {},
      previewInput: "{}",
      customCSS: "",
      cssErrors: "",
      showValidationErrors: false,
      toggleValidation: true,
      showDataPreview: true,
      showDataInput: true,
      editor: null,
      monacoOptions: {
        language: "json",
        lineNumbers: "off",
        formatOnPaste: true,
        formatOnType: true,
        automaticLayout: true,
        minimap: { enabled: false },
      },
      mockMagicVariables,
      previewComponents: [],
      optionsMenu: options,
      rendererKey: 0,
      renderComponent: "task-screen",
      ellipsisMenuOptions: {
        actions: [
          {
            content: this.$t("Export Screen"),
            icon: "fas fa-file-export",
            value: "export-screen",
            action: "beforeExportScreen()",
          },
          {
            value: "discard-draft",
            content: this.$t("Discard Draft"),
            icon: "fas fa-angle-double-down",
            hide: this.isVersionsInstalled,
          },
          {
            value: "create-template",
            content: this.$t("Save as Template"),
            icon: "fas fa-file-image",
          },
        ],
      },
      iframeHeight: "600px",
    };
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
    allWarnings() {
      return this.warnings.length;
    },
    allErrors() {
      return this.validationErrors.length;
    },
    validationErrors() {
      if (!this.toggleValidation) {
        return [];
      }

      const validationErrors = [];

      this.config.forEach((page) => {
        validationErrors.push(
          ...this.getValidationErrorsForItems(page.items, page),
        );
      });

      return validationErrors;
    },
    warnings() {
      const warnings = [];
      // Check if screen has watchers that use scripts
      const watchersWithScripts = this.watchers.filter(
        (watcher) => watcher.script.id.substr(0, 7) === "script-",
      ).length;
      if (watchersWithScripts > 0) {
        warnings.push({
          message: this.$t(
            "Using watchers with Scripts can slow the performance of your screen.",
          ),
        });
      }
      // Count form elements
      if (this.numberOfElements >= 25) {
        warnings.push({
          message: this.$t(
            "We recommend using fewer than 25 form elements in your screen for optimal performance.",
          ),
        });
      }
      return warnings;
    },
    autosaveApiCall() {
      return () => {
        this.setLoadingState(true);
        ProcessMaker.apiClient
          .put(`screens/${this.screen.id}/draft`, {
            title: this.screen.title,
            description: this.screen.description,
            type: this.screen.type,
            projects: this.screen.projects,
            config: this.config,
            computed: this.computed,
            custom_css: this.customCSS,
            watchers: this.watchers,
          })
          .then(() => {
            // Set draft status.
            this.setVersionIndicator(true);
            ProcessMaker.EventBus.$emit("save-changes");
          })
          .catch((error) => {
            if (error.response) {
              const { message } = error.response.data;
              ProcessMaker.alert(message, "danger");
            }
          })
          .finally(() => {
            this.setLoadingState(false);
          });
      };
    },
    closeHref() {
      return this.redirectUrl ? this.redirectUrl : "/designer/screens";
    },
  },
  watch: {
    customCSS(newCustomCSS) {
      this.preview.custom_css = newCustomCSS;
    },
  },
  mounted() {
    // TODO -- traducciones si se desea haciendo postabcks
    // const queryString = window.location.search;
    // const urlParams = new URLSearchParams(queryString);
    //
    // if (urlParams.get("lang")) {
    //   this.changeMode("preview");
    // }

    // To include another language in the Validator with variable processmaker
    this.user = window.ProcessMaker?.user;
    if (this.user?.lang) {
      Validator.useLang(window.ProcessMaker.user.lang);
    }

    Validator.register(
      "attr-value",
      (value) => value.match(/^[a-zA-Z0-9-_]+$/),
      "Must be letters, numbers, underscores or dashes",
    );
    this.countElements = debounce(this.countElements, 2000);
    this.mountWhenTranslationAvailable();
    this.countElements();
    // Display version indicator.
    this.setVersionIndicator();
    // Display ellipsis menu.
    this.setEllipsisMenu();
    ProcessMaker.EventBus.$on("show-create-template-modal", () => {
      this.$refs["create-template-modal"].show();
    });
  },
  methods: {
    ...mapMutations("globalErrorsModule", { setStoreMode: "setMode" }),
    translateScreen(language) {
      ProcessMaker.apiClient.get(`screens/${this.screen.id}/translate/${language}`)
        .then((response) => {
          this.preview.config = response.data;
        });
    },
    // eslint-disable-next-line func-names
    updateDataInput: debounce(function () {
      if (this.previewInputValid) {
        // Copy data over
        this.previewData = JSON.parse(this.previewInput);
        this.updateDataPreview();
      }
    }, 1000),
    // eslint-disable-next-line func-names
    updateDataPreview: debounce(function () {
      this.previewDataStringify = JSON.stringify(this.previewData, null, 2);
    }, 1000),
    monacoMounted(editor) {
      this.editor = editor;
      this.editor.updateOptions({ readOnly: true });
    },
    formatMonaco() {
      if (!this.editor) {
        return;
      }
      this.editor.updateOptions({ readOnly: false });
      setTimeout(() => {
        this.editor
          .getAction("editor.action.formatDocument")
          .run()
          .then(() => {
            this.editor.updateOptions({ readOnly: true });
          });
      }, 300);
    },
    countElements() {
      if (!this.$refs.renderer) {
        return;
      }
      this.$refs.renderer.countElements(this.config).then((allElements) => {
        this.numberOfElements = allElements.length;
      });
    },
    validationWarnings() {
      const warnings = [];

      if (this.type === formTypes.form && !this.containsSubmitButton()) {
        warnings.push(
          this.$t("Warning: Screens without save buttons cannot be executed."),
        );
      }

      warnings.push(...this.ariaWarnings());

      return warnings;
    },
    ariaWarnings() {
      const warnings = [];
      if (this.type !== formTypes.form) {
        return warnings;
      }

      this.allControls((item, pageName) => {
        if (!this.needsAriaLabel(item)) {
          return;
        }
        if (this.hasAriaLabel(item)) {
          return;
        }
        warnings.push(
          this.$t(
            "{{name}} on page {{pageName}} is not accessible to screen readers. "
            + "Please add a Label in the Variable section or an Aria Label in the Advanced section.",
            {
              name: item.config.name,
              pageName,
            },
          ),
        );
      });

      return warnings;
    },
    allControls(callback) {
      this.config.forEach((page) => {
        this.getControlsFromItems(callback, page.items, page.name);
      });
    },
    getControlsFromItems(callback, items, currentPageName) {
      if (!Array.isArray(items)) {
        return;
      }

      items.forEach((item) => {
        if (Array.isArray(item)) {
          this.getControlsFromItems(callback, item, currentPageName);
        } else if (Array.isArray(item.items)) {
          this.getControlsFromItems(callback, item.items, currentPageName);
        } else {
          callback(item, currentPageName);
        }
      });
    },
    hasAriaLabel(item) {
      if (item.config && (item.config.label || item.config.ariaLabel)) {
        return true;
      }
      return false;
    },
    needsAriaLabel(item) {
      return [
        "FormInput",
        "FormSelectList",
        "FormDatePicker",
        "FormCheckbox",
        "FileUpload",
        "FileDownload",
        "FormButton",
        "FormTextArea",
      ].includes(item.component);
    },
    mountWhenTranslationAvailable() {
      if (ProcessMaker.i18n.exists("Save") === false) {
        window.setTimeout(() => this.mountWhenTranslationAvailable(), 100);
      } else {
        const that = this;
        // Call our init lifecycle event
        ProcessMaker.EventBus.$emit("screen-builder-init", that);
        if (that.screen.type === "CONVERSATIONAL") {
          that.renderComponent = "ConversationalForm";
        }
        that.computed = that.screen.computed ? that.screen.computed : [];
        that.customCSS = that.screen.custom_css ? that.screen.custom_css : "";
        that.watchers = that.screen.watchers ? that.screen.watchers : [];
        that.previewInput = "{}";
        that.preview.custom_css = that.customCSS;
        ProcessMaker.EventBus.$emit("screen-builder-start", that);
        ProcessMaker.EventBus.$on(
          "save-screen",
          (value, onSuccess, onError) => {
            that.saveScreen(value, onSuccess, onError);
          },
        );
        ProcessMaker.EventBus.$on("screen-change", () => {
          this.handleAutosave();
        });
        ProcessMaker.EventBus.$on("screen-close", () => {
          this.onClose();
        });
        ProcessMaker.EventBus.$on("screen-discard", () => {
          that.discardDraft();
        });
      }
    },
    changeMode(mode) {
      if (mode === "editor") {
        this.$refs.menuScreen.changeItem("button_design", {
          variant: "secondary",
        });
        this.$refs.menuScreen.changeItem("button_preview", {
          variant: "outline-secondary",
        });
        this.$refs.menuScreen.sectionRight = true;
      }
      if (mode === "preview") {
        this.changeDeviceScreen('desktop');
        this.$refs.menuScreen.changeItem("button_design", {
          variant: "outline-secondary",
        });
        this.$refs.menuScreen.changeItem("button_preview", {
          variant: "secondary",
        });
        this.$refs.menuScreen.sectionRight = false;

        if (this.$refs.renderer) {
          this.$refs.renderer.hasSubmitted(false);
        }
      }
      this.mode = mode;
      this.setStoreMode(this.mode);
      this.previewData = this.previewInputValid
        ? JSON.parse(this.previewInput)
        : {};
      this.rendererKey += 1;
      if (mode === "preview") {
        this.$dataProvider.flushScreenCache();
        this.preview.config = cloneDeep(this.config);
        this.preview.computed = cloneDeep(this.computed);
        this.preview.customCSS = cloneDeep(this.customCSS);
        this.preview.watchers = cloneDeep(this.watchers);
      } else {
        this.$refs.builder.refreshContent();
      }
    },
    changeDeviceScreen(deviceScreen) {
      this.$refs.menuScreen.changeItem("button_preview_desktop", {
        variant: deviceScreen === "desktop" ? "secondary" : "outline-secondary",
      });
      this.$refs.menuScreen.changeItem("button_preview_mobile", {
        variant: deviceScreen === "mobile" ? "secondary" : "outline-secondary",
      });

      this.deviceScreen = deviceScreen;

      this.$nextTick(() => {
        if (this.$refs.renderer) {
          this.$refs.renderer.checkIfIsMobile();
        }
      });
    },
    onUpdate(data) {
      this.updateDataPreview();
      ProcessMaker.EventBus.$emit("form-data-updated", data);
    },
    getValidationErrorsForItems(inputItems, page) {
      const validationErrors = [];
      const items = Array.isArray(inputItems) ? inputItems : [inputItems];

      items.forEach((item) => {
        if (item.container) {
          item.items.forEach((containerItems) => {
            validationErrors.push(
              ...this.getValidationErrorsForItems(containerItems, page),
            );
          });
        }

        const data = item.config || {};
        const rules = {};

        item.inspector.forEach((property) => {
          if (property.config.validation) {
            if (
              property.config.validation.includes(
                "regex:/^(?:[A-Za-z])(?:[0-9A-Z_.a-z])*(?<![.])$/",
              )
            ) {
              const validationRuleArray = property.config.validation.split("|");
              validationRuleArray[0] = "regex:/^(?:[A-Za-z])(?:[0-9A-Z_.a-z])*[^.]$/";
              rules[property.field] = validationRuleArray.join("|");
            } else {
              rules[property.field] = property.config.validation;
            }
          }
        });

        const validator = new Validator(data, rules);

        // Validation will not run until you call passes/fails on it
        let passes;
        try {
          passes = validator.passes();
        } catch (err) {
          // Prevent errors during validation break the screen builder loading
          passes = false;
        }
        if (!passes) {
          Object.keys(validator.errors.errors).forEach((field) => {
            validator.errors.errors[field].forEach((error) => {
              validationErrors.push({
                message: error,
                page,
                item,
              });
            });
          });
        }
      });

      return validationErrors;
    },
    containsSubmitButton() {
      return this.config.some((config) => this.itemsContainSubmitButton(config.items));
    },
    isSubmitButton(item) {
      return item.component === "FormButton" && item.config.event === "submit";
    },
    itemsContainSubmitButton(inputItems) {
      const items = Array.isArray(inputItems) ? inputItems : [inputItems];

      return items.some((item) => (item.container
        ? item.items.some(this.itemsContainSubmitButton)
        : this.isSubmitButton(item)));
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
    undoAction() {
      this.$refs.builder.undo();
    },
    redoAction() {
      this.$refs.builder.redo();
    },
    openComputedProperties() {
      this.$refs.computedProperties.show();
    },
    openCustomCSS() {
      this.$refs.customCSS.show();
    },
    updateConfig(newConfig) {
      this.config = newConfig;
      // Reset the preview data with clean object to start
      this.previewData = {};
      this.refreshSession();
      ProcessMaker.EventBus.$emit("new-changes");
      // Recount number of elements
      this.countElements();
    },
    previewSubmit() {
      // eslint-disable-next-line no-alert
      alert("Preview Form was Submitted");
    },
    addControl(
      control,
      rendererComponent,
      rendererBinding,
      builderComponent,
      builderBinding,
    ) {
      this.translateControl(control);
      // Add it to the renderer
      if (!this.$refs.renderer) {
        return;
      }
      this.$refs.renderer.$options.components[rendererBinding] = rendererComponent;
      // Add it to the form builder
      this.$refs.builder.addControl(control, builderComponent, builderBinding);
    },
    setGroupOrder(config) {
      this.$refs.builder.setGroupOrder(config);
    },
    translateControl(inputControl) {
      const control = { ...inputControl };

      if (control.label) {
        control.label = this.$t(control.label);
      }
      if (control.config && control.config.label) {
        control.config.label = this.$t(control.config.label);
      }
      if (control.config && control.config.helper) {
        control.config.helper = this.$t(control.config.helper);
      }

      // translate option list items
      if (control.config.options && Array.isArray(control.config.options)) {
        control.config.options = control.config.options.map(($item) => {
          if ($item.content) {
            return { ...$item, content: this.$t($item.content) };
          }
          return $item;
        });
      }

      // translate inspector items
      if (control.inspector) {
        control.inspector.forEach(($item) => this.translateControl($item));
      }
    },
    addPreviewComponent(component) {
      this.previewComponents.push(component);
    },
    refreshSession: _.throttle(() => {
      ProcessMaker.apiClient({
        method: "POST",
        url: "/keep-alive",
        baseURL: "/",
      });
    }, 60000),
    onInput() {
      ProcessMaker.EventBus.$emit("screen-change");
    },
    exportScreen() {
      ProcessMaker.apiClient
        .post(`screens/${this.screen.id}/export`)
        .then((response) => {
          window.open(response.data.url);
          ProcessMaker.alert(this.$t("The screen was exported."), "success");
        })
        .catch((error) => {
          ProcessMaker.alert(error.response.data.error, "danger");
        });
    },
    discardDraft() {
      ProcessMaker.apiClient
        .post(`/screens/${this.screen.id}/close`)
        .then(() => {
          window.location.reload();
        });
    },
    saveScreen(exportScreen, onSuccess, onError) {
      if (this.allErrors !== 0) {
        ProcessMaker.alert(
          this.$t("This screen has validation errors."),
          "danger",
        );
      } else {
        this.validationWarnings().forEach((warning) => ProcessMaker.alert(warning, "warning"));
        ProcessMaker.apiClient
          .put(`screens/${this.screen.id}`, {
            title: this.screen.title,
            description: this.screen.description,
            type: this.screen.type,
            config: this.config,
            projects: this.screen.projects,
            computed: this.computed,
            custom_css: this.customCSS,
            watchers: this.watchers,
          })
          .then((response) => {
            if (exportScreen) {
              this.exportScreen();
            }
            ProcessMaker.alert(this.$t("Successfully saved"), "success");
            // Set published status.
            this.setVersionIndicator(false);
            ProcessMaker.EventBus.$emit("save-changes");
            if (typeof onSuccess === "function") {
              onSuccess(response);
            }

            if (!exportScreen) {
              if (this.processId) {
                window.location = `/modeler/${this.processId}`;
              }

              window.ProcessMaker.EventBus.$emit("redirect");
            }
          })
          .catch((err) => {
            if (typeof onError === "function") {
              onError(err);
            }
          });
      }
    },
    setVersionIndicator(isDraft = null) {
      if (this.isVersionsInstalled) {
        this.$refs.menuScreen.removeItem("VersionIndicator");
        this.$refs.menuScreen.addItem(
          {
            id: "VersionIndicator",
            type: "VersionIndicator",
            section: "rightTop",
            options: {
              is_draft: isDraft ?? this.isDraft,
            },
          },
          0,
        );
      }
    },
    setLoadingState(isLoading = false) {
      if (this.isVersionsInstalled) {
        this.$refs.menuScreen.removeItem("SavedNotification");
        this.$refs.menuScreen.addItem(
          {
            id: "SavedNotification",
            type: "SavedNotification",
            section: "right",
            options: {
              is_loading: isLoading,
            },
          },
          1,
        );
      }
    },
    setEllipsisMenu() {
      this.$refs.menuScreen.addItem(
        {
          id: "EllipsisMenu",
          type: "EllipsisMenu",
          section: "right",
          options: {
            actions: [...this.ellipsisMenuOptions.actions],
            data: {},
            divider: false,
            lauchpad: true,
          },
        },
        4,
      );
    },
  },
};
</script>

<style lang="scss">
$validation-panel-bottom: 3.5rem;
$validation-panel-right: 0;
$validation-panel-height: 10rem;
$validation-panel-width: 41rem;
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

.validation-panel button {
  text-transform: none !important;
}

.preview-inspector {
  max-width: 265px;
}

.data-collapse {
  height: 225px;
}
.editor {
  height: 30em;
}
.tree-button {
  box-shadow: 2px 2px rgba($color: #000000, $alpha: 1);
}
.device-mobile {
  width: 480px;
  border: 1px solid rgba(0, 0, 0, 0.125);
  margin: 0px auto;
}
.device-screen {
  width: 100%;
}
.btn-platform {
  background-color: #ffff;
  color: #6a7888;
  padding: 8px 8px 2px 8px;
  font-size: 1rem !important;
}
.btn-platform:hover {
  color: #6a7888;
}
.page-dropdown-menu {
  min-width: 333px;
  max-height: 26rem;
  overflow-y: auto;
  scrollbar-width: thin;
  .dropdown-item {
   font-size: 1rem !important;
  };
}
</style>
