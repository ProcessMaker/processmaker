<template>
  <div class="card h-100">
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
    <div v-for="page in printablePages" :key="page" class="card">
      <div class="card-body h-100" :style="cardStyles">
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
        const pages = [0];
        if (this.rowData.config instanceof Array) {
          this.rowData.config.forEach(page => {
            this.findPagesInNavButtons(page, pages);
          });
        }
        return pages;
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
          this.$refs.print.forEach((page, index) => {
            if (page.setCurrentPage) {
              page.setCurrentPage(this.printablePages[index]);
            }
          });
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
          found.indexOf(page) === -1 ? found.push(page) : null;
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
        window.print();
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

