<template>
  <div class="card h-100">
    <div v-show="disabled" id="cover-spin" class="card-body h-100"></div>
    <div slot="modal-header" class="w-100 d-print-none" align="right">
      <button
        type="button"
        @click="print"
        class="btn btn-secondary ml-2"
        :aria-label="$t('Print')"
        :disabled="disabled"
      >
        <i class="fas fa-print"></i> {{ $t("Print") }}
      </button>
    </div>
    <div v-for="page in printablePages" :key="page" class="card">
      <div class="card-body h-100" style="pointer-events: none">
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
    <div slot="modal-header" class="w-100 d-print-none" align="right">
      <button
        type="button"
        @click="print"
        class="btn btn-secondary ml-2"
        :aria-label="$t('Print')"
        :disabled="disabled"
      >
        <i class="fas fa-print"></i> {{ $t("Print") }}
      </button>
    </div>
  </div>
</template>

<script>

  import Vue from 'vue'
  import {VueFormRenderer} from '@processmaker/screen-builder';
  import '@processmaker/screen-builder/dist/vue-form-builder.css';

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
    },
    data() {
      return {
        interval: null,
        disabled: true,
      }
    },
    computed: {
      json() {
        const json = JSON.parse(JSON.stringify(this.rowData.config));
        return this.disableForm(json);
      },
      formData() {
        return this.rowData.data ? this.rowData.data : {};
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
    watch: {
      "rowData.config": {
        deep: true,
        handler() {
          this.loadPages();
        }
      }
    }
  }
</script>

<style scoped>
#cover-spin {
    position:fixed;
    width:100%;
    left:0;right:0;top:0;bottom:0;
    background-color: rgba(255,255,255,0.7);
    z-index:9999;
    display:none;
}

@-webkit-keyframes spin {
	from {-webkit-transform:rotate(0deg);}
	to {-webkit-transform:rotate(360deg);}
}

@keyframes spin {
	from {transform:rotate(0deg);}
	to {transform:rotate(360deg);}
}

#cover-spin::after {
    content:'';
    display:block;
    position:absolute;
    left:48%;top:40%;
    width:40px;height:40px;
    border-style:solid;
    border-color:black;
    border-top-color:transparent;
    border-width: 4px;
    border-radius:50%;
    -webkit-animation: spin .8s linear infinite;
    animation: spin .8s linear infinite;
}
</style>
