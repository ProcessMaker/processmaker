<template>
  <div class="h-100">
    <b-card no-body class="h-100 bg-white" id="app">
      <!-- Card Header -->
      <b-card-header>
        <b-row>
          <b-col>
            <b-button-group size="sm">
              <b-button :variant="displayBuilder? 'secondary' : 'outline-secondary'" @click="mode = 'editor'">
                <i class="fas fa-drafting-compass pr-1"></i>{{ $t('Design') }}
              </b-button>
              <b-button :variant="!displayBuilder? 'secondary' : 'outline-secondary'" @click="mode = 'preview'">
                <i class="fas fa-cogs pr-1"></i>{{ $t('Preview') }}
              </b-button>
            </b-button-group>
          </b-col>

          <b-col class="text-right" v-if="displayBuilder && !displayPreview">
            <div class="btn-group btn-group-sm" role="group" aria-label="Basic example">
              <button type="button" class="btn btn-secondary" title="Calculated Properties" @click="openComputedProperties">
                <i class="fas fa-flask"></i>
                Calcs
              </button>
              <button type="button" class="btn btn-secondary mr-2" title="Custom CSS" @click="openCustomCSS">
                <i class="fab fa-css3"></i>
                CSS
              </button>
            </div>
            <button @click="saveScreen(false)" type="button" class="btn btn-secondary btn-sm ml-1"><i class="fas fa-save"></i></button>
          </b-col>

        </b-row>
      </b-card-header>

      <!-- Card Body -->
      <b-card-body class="overflow-auto ml-3 mr-3">
        <!-- Vue-form-builder -->
        <vue-form-builder :validationErrors="validationErrors" ref="builder" @change="updateConfig" :class="displayBuilder ? 'd-flex' : 'd-none'" />

        <!-- Preview -->
        <b-row class="h-100" id="preview" v-show="displayPreview">
          <b-col cols="8" class="overflow-auto h-100 border rounded">
            <vue-form-renderer ref="renderer"
              v-model="previewData"
              class="p-3 overflow-auto"
              @submit="previewSubmit"
              :config="config"
              :computed="computed"
              :custom-css="customCSS"
              v-on:css-errors="cssErrors = $event"/>
          </b-col>

          <b-col cols="4" class="overflow-hidden h-100 pr-0 pl-4">
            <b-card no-body class="p-0">
              <b-card-header class="stick-top">
                Inspector
              </b-card-header>

              <b-card-body class="p-0">
                <b-button v-b-toggle.dataPreview variant="outline"
                  class="text-left card-header d-flex align-items-center w-100"
                  @click="showDataPreview = !showDataPreview">
                  <i class="fas fa-file-code mr-2"></i>
                    {{ $t('Data Preview') }}
                  <i class="fas fa-angle-down ml-auto" :class="{ 'fas fa-angle-right' : !showDataPreview }"></i>
                </b-button>

                <b-collapse id="dataPreview" visible class="mt-2 overflow-auto">
                  <vue-json-pretty  :data="previewData" class="p-2 data-height"></vue-json-pretty>
                </b-collapse>

                <b-button v-b-toggle.dataInput variant="outline"
                  class="text-left card-header d-flex align-items-center w-100"
                  @click="showDataInput = !showDataInput">
                  <i class="fas fa-file-import mr-2"></i>
                    {{ $t('Data Input') }}
                  <i class="fas fa-angle-down ml-auto" :class="{ 'fas fa-angle-right' : !showDataInput }"></i>
                </b-button>

                <b-collapse id="dataInput" visible class="overflow-auto">
                  <form-text-area class="data-height h-100 dataInput"  v-model="previewInput"></form-text-area>
                </b-collapse>
              </b-card-body>
            </b-card>
          </b-col>
        </b-row>
      </b-card-body>

      <!-- Card Footer -->
      <b-card-footer class="d-flex d-flex justify-content-end align-items-center">
        <b-form-checkbox v-model="toggleValidation" name="check-button" switch>
          Screen Validation
        </b-form-checkbox>

        <div class="ml-3" @click="showValidationErrors = !showValidationErrors">
          <button type="button" class="btn btn-light btn-sm">
            <i class="fas fa-angle-double-up"></i>
            {{ $t('Open Console') }}
            <span v-if="allErrors === 0" class="badge badge-success">
              <i class="fas fa-check-circle "></i>
              {{ $t(allErrors) }}
            </span>

            <span v-else class="badge badge-danger">
              <i class="fas fa-times-circle "></i>
              {{ $t(allErrors) }}
            </span>
          </button>
        </div>

        <div v-if="showValidationErrors" class="validation-panel position-absolute shadow border overflow-auto" :class="{'d-block':showValidationErrors && validationErrors.length}">
            <div v-if="!previewInputValid" class="p-3 font-weight-bold text-dark">
              <i class="fas fa-times-circle text-danger mr-3"></i>
              {{$t('Invalid JSON Data Object')}}
            </div>
            <b-button variant="link" class="validation__message d-flex align-items-center p-3"
                      v-for="(validation,index) in validationErrors"
                      :key="index"
                      @click="focusInspector(validation)">
              <i class="fas fa-times-circle text-danger d-block mr-3"></i>
              <span class="ml-2 text-dark font-weight-bold text-left">
                {{ validation.item.component }}
                <span class="d-block font-weight-normal">{{ validation.message }}</span>
              </span>
            </b-button>
            <span v-if="!allErrors" class="d-flex justify-content-center align-items-center h-100">No Errors</span>
        </div>
      </b-card-footer>
    </b-card>
    <!-- Modals -->
    <computed-properties v-model="computed" ref="computedProperties"></computed-properties>
    <custom-CSS v-model="customCSS" ref="customCSS" :cssErrors="cssErrors"/>
  </div>
</template>

<script>
  import {VueFormBuilder, VueFormRenderer, FormBuilderControls as controlConfig} from "@processmaker/spark-screen-builder";
  import "@processmaker/spark-screen-builder/dist/vue-form-builder.css";
  import "@processmaker/vue-form-elements/dist/vue-form-elements.css";
  import VueJsonPretty from 'vue-json-pretty';

  // Bring in our initial set of controls
  import globalProperties from "@processmaker/spark-screen-builder/src/global-properties";
  import {
    FormTextArea,
  } from "@processmaker/vue-form-elements";
  import _ from "lodash";

import Validator from "validatorjs";

  Validator.register('attr-value', value => {
    return value.match(/^[a-zA-Z0-9-_]+$/);
  }, 'Must be letters, numbers, underscores or dashes');

console.log("VueFormBuilder", VueFormBuilder);
  export default {
    props: ["process", "screen", 'permission'],
    data() {
      return {
        mode: "editor",
        // Computed properties
        computed: [],
        config: [
          {
            name: "Default",
            computed: [],
            items: []
          }
        ],
        previewData: {},
        previewInput: '{}',
        customCSS: "",
        cssErrors: '',
        showValidationErrors: false,
        toggleValidation: true,
        showDataPreview: false,
        showDataInput: false,
      };
    },
    components: {
      VueFormBuilder,
      VueFormRenderer,
      VueJsonPretty,
      FormTextArea
    },
    watch: {
      mode(mode) {
        if (mode === 'preview') {
          this.previewData = this.previewInput ? JSON.parse(this.previewInput) : null;
        }
      },
      config() {
        // Reset the preview data with clean object to start
        this.previewData = {}
      },
      previewInput() {
        if (this.previewInputValid) {
          // Copy data over
          this.previewData = JSON.parse(this.previewInput)
        } else {
          this.previewData = {}
        }
      }
    },
    computed: {
      previewInputValid() {
        try {
          JSON.parse(this.previewInput)
          return true
        } catch (err) {
          return false
        }
      },
      displayBuilder() {
        return this.mode === 'editor';
      },
      displayPreview() {
        return this.mode === 'preview';
      },
      allErrors() {
        let errorCount = 0;

        if(!this.previewInputValid) {
          errorCount++;
        }

        return this.validationErrors.length + errorCount
      },
      validationErrors() {
        const validationErrors = [];
        this.config.forEach(page => {
          page.items.forEach(item => {
            let data = item.config ? item.config : {};
            let rules = {};
            item.inspector.forEach(property => {
              if (property.config.validation) {
                rules[property.field] = property.config.validation;
              }
            });
            let validator = new Validator(data, rules);
            // Validation will not run until you call passes/fails on it
            if(!validator.passes()) {
              Object.keys(validator.errors.errors).forEach(field => {
                validator.errors.errors[field].forEach(error => {
                  validationErrors.push({
                    message: error,
                    page: page,
                    item: item,
                  });
                });
              });
            }
          });
        });
        return this.toggleValidation ? validationErrors : [] ;
      },
    },
    mounted() {
      // Call our init lifecycle event
      ProcessMaker.EventBus.$emit("screen-builder-init", this);
      this.$refs.builder.config = this.screen.config
        ? this.screen.config
        : [
          {
            name: "Default",
            items: []
          }
        ];
      this.computed = this.screen.computed ? this.screen.computed : [];
      this.customCSS = this.screen.custom_css ? this.screen.custom_css : "";
      this.$refs.builder.computed = this.screen.computed
        ? this.screen.computed
        : [];
      if (this.screen.title) {
        this.$refs.builder.config[0].name = this.screen.title;
      }
      this.updatePreview(new Object());
      this.previewInput = "{}";
      ProcessMaker.EventBus.$emit("screen-builder-start", this);
    },
    methods: {
      beforeExportScreen() {
        this.saveScreen(true);
      },
      focusInspector(validate) {
        this.$refs.builder.focusInspector(validate);
      },
      openComputedProperties() {
        this.$refs.computedProperties.show();
      },
      openCustomCSS() {
        this.$refs.customCSS.show();
      },
      updateConfig(newConfig) {
        this.config = newConfig
        this.refreshSession();
      },
      updatePreview(data) {
        this.previewData = data
      },
      previewSubmit() {
        alert("Preview Form was Submitted")
      },
      addControl(control, rendererComponent, rendererBinding, builderComponent, builderBinding) {
        // Add it to the renderer
        this.$refs.renderer.$options.components[rendererBinding] = rendererComponent;
        // Add it to the form builder
        this.$refs.builder.addControl(control, builderComponent, builderBinding)
      },
      refreshSession: _.throttle(function() {
        ProcessMaker.apiClient({
            method: 'POST',
            url: '/keep-alive',
            baseURL: '/',
          })
      }, 60000),
      onClose() {
        window.location.href = "/processes/screens";
      },
      beforeExportScreen() {
        this.saveScreen(true);
      },
      exportScreen() {
        ProcessMaker.apiClient.post('screens/' + this.screen.id + '/export')
          .then(response => {
            window.open(response.data.url);
            ProcessMaker.alert(this.$t('The screen was exported.'), 'success');
          })
          .catch(error => {
            ProcessMaker.alert(error.response.data.error, 'danger');
          });
      },
      saveScreen(exportScreen) {
        if (this.allErrors !== 0) {
          ProcessMaker.alert(this.$t("This screen has validation errors."), "danger");
        } else {
          ProcessMaker.apiClient
            .put("screens/" + this.screen.id, {
              title: this.screen.title,
              description: this.screen.description,
              type: this.screen.type,
              config: this.config,
              computed: this.computed,
              custom_css: this.customCSS
            })
            .then(response => {
              if (exportScreen) {
                this.exportScreen();
              }
              ProcessMaker.alert(this.$t(" Successfully saved"), "success");
            });
        }
      }
    }
  };
</script>

<style lang="scss">
    @import "~bootstrap/dist/css/bootstrap";

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
      background: #f7f7f7;
      height: 10rem;
      width: 21.35rem;
      bottom: 4rem;
      right: 0;
    }

    .dataInput {
      margin-top: -25px;
    }
</style>
