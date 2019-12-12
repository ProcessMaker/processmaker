<template>
  <div class="card h-100">
      <div v-for="page in printablePages" :key="page" class="card">
      <div class="card-body h-100" style="pointer-events:none;">
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
        if (this.json instanceof Array) {
          this.json.forEach(page => {
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
      if (this.canPrint) {
        this.print();
      }
    },
    methods: {
      findPagesInNavButtons(object, found = []) {
        if (object.items) {
          object.items.forEach(item => {
            this.findPagesInNavButtons(item, found);
          });
        } else if (object instanceof Array) {
          object.forEach(item => {
            this.findPagesInNavButtons(item, found);
          });
        } else if (object.config && object.config.event === 'pageNavigate') {
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
        immediate: true,
        handler() {
          this.$nextTick(() => {
            this.$refs.print.forEach((page, index) => {
              page.currentPage = this.printablePages[index];
            });
          });
        }
      }
    }
  }
</script>
