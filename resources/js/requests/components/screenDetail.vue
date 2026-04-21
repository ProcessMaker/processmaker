<template>
  <div class="card">
    <b-overlay
      id="overlay-background"
      :show="disabled"
      :variant="variant"
      :opacity="opacity"
      :blur="blur"
      rounded="sm"
    >
      <div class="w-100 d-print-none" align="right">
        <button
          type="button"
          @click="print"
          class="btn btn-secondary ml-2"
          :aria-label="$t('Print')"
          v-if="canPrint"
          :disabled="disabled"
        >
          <i class="fas fa-print"></i> {{ $t("Print") }}
        </button>
      </div>
      <div class="card-body" :style="cardStyles">
        <component
          ref="print"
          :is="component"
          v-model="formData"
          :data="formData"
          @update="onUpdate"
          :config="json"
          csrf-token=""
          submiturl=""
          token-id=""
        />
      </div>
      <div class="w-100 d-print-none" align="right">
        <button
          type="button"
          @click="print"
          v-if="canPrint"
          class="btn btn-secondary ml-2"
          :aria-label="$t('Print')"
          :disabled="disabled"
        >
          <i class="fas fa-print"></i> {{ $t("Print") }}
        </button>
      </div>
    </b-overlay>
  </div>
</template>

<script>

  import Vue from 'vue'
  import {VueFormRenderer} from '@processmaker/screen-builder';

  Vue.component('vue-form-renderer', VueFormRenderer);

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
      timeoutOnLoad:{
        type: Boolean,
        default: false
      }
    },
    data() {
      return {
        interval: null,
        disabled: true,
        variant: 'transparent',
        opacity: 0.85,
        blur: '2px',
        isPhotoVideo: false,
        cardStyles: 'pointer-events: none;',
        iFramePostedData: null,
      }
    },
    computed: {
      json() {
        const json = JSON.parse(JSON.stringify(this.rowData.config));
        return this.disableForm(json);
      },
      formData: {
        get() {
          if(this.iFramePostedData) {
            return this.iFramePostedData;
          }
          return this.rowData.data ? this.rowData.data : {};
        }, 
        set() {

        }
      },
      printablePages() {
        // New strategy: always return only page 0
        // This avoids any problem with the detection of pages
        return [0];
      },
      component() {
        if ('renderComponent' in this.rowData.config) {
          return this.rowData.config.renderComponent;
        }
        return 'vue-form-renderer';
      }
    },
    mounted() {
      $('#cover-spin').show(0);
      window.ProcessMaker.apiClient.requestCount = 0;
      window.ProcessMaker.apiClient.requestCountFlag = true;
      if (this.timeoutOnLoad) {
        window.addEventListener('load', () => {
          setTimeout(() => {
            this.interval = setInterval(this.printWhenNoRequestsArePending, 1000);
          }, 750);

          setTimeout(() => {
            this.closeRequestCount();
            if (window.ProcessMaker.apiClient.requestCountFlag) {
              this.disabled = false;
            }
          }, 30000);
        });
      } else {
          this.disabled = false;
      }
      this.loadPages();
    },
    methods: {
      closeRequestCount() {
        window.ProcessMaker.apiClient.requestCount = 0;
        window.ProcessMaker.apiClient.requestCountFlag = false;
      },
      printWhenNoRequestsArePending() {
        if (this.canPrint && window.ProcessMaker.apiClient.requestCount === 0) {
          clearInterval(this.interval);
          this.closeRequestCount();
          this.disabled = false;
        }
      },
      loadPages() {
        this.$nextTick(() => {
          if (this.$refs.print && this.$refs.print.setCurrentPage) {
            // Force page 0
            this.$refs.print.setCurrentPage(0);
          }
        });
      },
      findPagesInNavButtons(object, found = []) {
        if (object.items) {
          object.items.forEach(item => {
            this.findPagesInNavButtons(item, found);
          });
        } else if (object instanceof Array) {
          object.forEach(item => {
            this.findPagesInNavButtons(item, found);
          });
        } else if (object.config && object.config.event === 'pageNavigate' && object.config.eventData) {
          const page = parseInt(object.config.eventData);
          if (found.indexOf(page) === -1) {
            found.push(page);
          }
        }
        // Also search in the structure of pages of the form
        if (object.component === 'FormMultiColumn' && object.config && object.config.pages) {
          object.config.pages.forEach((page, index) => {
            if (found.indexOf(index) === -1) {
              found.push(index);
            }
          });
        }
        // Search in components that can have pagination
        if (object.component === 'FormPage' && object.config && object.config.page) {
          const page = parseInt(object.config.page);
          if (found.indexOf(page) === -1) {
            found.push(page);
          }
        }
      },
      hasRealContent(item) {
        // Verify if the element has real content that should be shown
        if (!item) return false;
        
        // If it is a component that should not be shown in print, it does not have content
        if (item.component === 'FormButton' || item.component === 'FileUpload' || item.component === 'PhotoVideo') {
          return false;
        }
        
        // If it has items, verify if any of them has content
        if (item.items && item.items.length > 0) {
          return item.items.some(child => this.hasRealContent(child));
        }
        
        // If it is an array, verify if any of them has content
        if (item instanceof Array) {
          return item.some(child => this.hasRealContent(child));
        }
        
        // If it has a valid component, it has content
        if (item.component && item.component !== 'FormButton' && item.component !== 'FileUpload' && item.component !== 'PhotoVideo') {
          return true;
        }
        
        return false;
      },
      findAllPagesWithContent(config, pages) {
        if (config instanceof Array) {
          config.forEach((item, index) => {
            if (this.hasRealContent(item)) {
              if (pages.indexOf(index) === -1) {
                pages.push(index);
              }
            }
            // Search recursively in the items
            if (item.items) {
              this.findAllPagesWithContent(item.items, pages);
            }
          });
        } else if (config.items) {
          this.findAllPagesWithContent(config.items, pages);
        } else if (config.component && config.component !== 'FormButton' && config.component !== 'FileUpload' && config.component !== 'PhotoVideo') {
          // If it is a valid component, include page 0
          if (pages.indexOf(0) === -1) {
            pages.push(0);
          }
        }
      },
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
            } else if (json[i].component === 'PhotoVideo') {
              json.splice(i, 1);
              this.isPhotoVideo === true;
            } else {
              this.disableForm(json[i]);
            }
          }
        }
        if (json.config !== undefined) {
          json.config.disabled = true;
          json.config.readonly = true;
          json.config.editable = false;
          json.config._perPage = Number.MAX_SAFE_INTEGER;
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
        // Ensure that the content is rendered completely before printing
        this.$nextTick(() => {
          // Force the re-rendering of all components
          this.$forceUpdate();
          
          // Small delay to ensure that the DOM is updated
          setTimeout(() => {
            // Apply specific styles for print
            document.body.classList.add('printing');
            
            // Open the print dialog
            window.print();
            
            // Clean the class after a time
            setTimeout(() => {
              document.body.classList.remove('printing');
            }, 1000);
          }, 100);
        });
        return true;
      }
    },
    watch: {
      "rowData.config": {
        deep: true,
        handler() {
          this.loadPages();
        }
      },
      isPhotoVideo() {
        this.cardStyles = this.isPhotoVideo ? 'pointer-events : all' : 'pointer-events : none';
      }
    }
  }
</script>

<style scoped>
@media print {
  .card {
    overflow: visible !important;
    height: auto !important;
    max-height: none !important;
    page-break-inside: avoid;
  }
  
  .card-body {
    overflow: visible !important;
    height: auto !important;
    max-height: none !important;
  }
  
  .h-100 {
    height: auto !important;
    max-height: none !important;
  }
  
  /* Ensure that all elements of the form are visible */
  .card-body * {
    overflow: visible !important;
  }
  
  /* Avoid that the elements are cut */
  .form-group,
  .form-control,
  .input-group {
    overflow: visible !important;
    height: auto !important;
  }
}

/* Additional styles when printing */
body.printing .card {
  overflow: visible !important;
  height: auto !important;
  max-height: none !important;
}

body.printing .card-body {
  overflow: visible !important;
  height: auto !important;
  max-height: none !important;
}

body.printing .h-100 {
  height: auto !important;
  max-height: none !important;
}

/* Avoid empty pages */
@media print {
  .card:empty,
  .card-body:empty {
    display: none !important;
  }
  
  /* Ensure that the content is shown correctly */
  .card {
    page-break-inside: avoid;
    break-inside: avoid;
  }
}
</style>

