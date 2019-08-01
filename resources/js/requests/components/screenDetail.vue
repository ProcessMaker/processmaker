<template>
  <div class="card">
    <div class="card-body">
      <vue-form-renderer ref="print" v-model="formData" :config="json"/>
    </div>
    <div class="card-footer d-print-none" v-if="canPrint">
        <button type="button" class="btn btn-secondary float-right" @click="print">
            <i class="fas fa-print"></i> {{ $t('Print') }}
        </button>
    </div>

  </div>
</template>

<script>
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
      }
    },
    computed: {
      json() {
        return this.disableForm(this.rowData.config);
      },
      formData() {
        return this.rowData.data ? this.rowData.data : {};
      },
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
          for (let item of json) {
            if (item.component === 'FormButton') {
              json.splice(json.indexOf(item), 1);
            } else {
              this.disableForm(item);
            }
          }
        }
        if (json.config !== undefined) {
          json.config.disabled = true;
          json.config.readonly = true;
        }
        if (json.items !== undefined) {
          this.disableForm(json.items);
        }
        return json;
      },
      print() {
        window.print();
        return true;
      }
    },
  }
</script>