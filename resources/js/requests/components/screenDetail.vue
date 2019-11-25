<template>
  <div class="card h-100">
    <div class="card-body h-100" style="pointer-events:none;">
      <advanced-screen-frame
          v-if="advanced"
          :config="json"
          csrf-token=""
          submiturl=""
          token-id=""
          data="formData"
      >
      </advanced-screen-frame>

      <vue-form-renderer v-else ref="print" v-model="formData" @update="onUpdate" :config="json"/>
    </div>
    <div class="card-footer d-print-none" v-if="canPrint">
      <button type="button" class="btn btn-secondary float-right" @click="print">
        <i class="fas fa-print"></i> {{ $t('Print') }}
      </button>
    </div>

  </div>
</template>

<script>

  import Vue from 'vue'
  import {VueFormRenderer} from '@processmaker/screen-builder';
  import '@processmaker/screen-builder/dist/vue-form-builder.css';
  import FileUpload from "../../processes/screen-builder/components/form/file-upload";
  import FileDownload from "../../processes/screen-builder/components/file-download";
  import AdvancedScreenFrame from "../../processes/screen-builder/advancedScreenFrame";

  Vue.component('vue-form-renderer', VueFormRenderer);
  Vue.component('FileUpload', FileUpload);
  Vue.component('FileDownload', FileDownload);
  Vue.component('advanced-screen-frame', AdvancedScreenFrame);

  export default {
    inheritAttrs: false,
    props: {
      rowData: {
        type: Object,
        required: true
      },
      rowIndex: {
        type: Number
      },
      canPrint: {
        type: Boolean,
        default: false
      },
    },
    computed: {
      json() {
        if (Array.isArray(this.rowData.config)) {
          return this.disableForm(this.rowData.config);
        }
        return this.rowData.config;
      },
      formData() {
        return this.rowData.data ? this.rowData.data : {};
      },
      advanced() {
        return this.rowData.type === 'FORM (ADVANCED)';
      }
    },
    mounted() {
      if (this.canPrint) {
        this.print();
      }
    },
    methods: {
      /**
       * Disable the form items.
       *
       * @param {array|object} json
       * @returns {array|object}
       */
      disableForm(json) {
        if (json instanceof Array) {
          for (let i = json.length - 1; i >= 0; i--) {
            if (json[i].component === 'FormButton' || json[i].component === 'FileUpload') {
              json.splice(i, 1);
            } else {
              this.disableForm(json[i]);
            }
          }
        }
        if (json.config !== undefined) {
          json.config.disabled = true;
          json.config.readonly = true;
          json.config.editable = false;
        }
        if (json.items !== undefined) {
          this.disableForm(json.items);
        }
        return json;
      },
      onUpdate(data) {
        ProcessMaker.EventBus.$emit('form-data-updated', data);
      },
      print() {
        window.print();
        return true;
      }
    },
  }
</script>
