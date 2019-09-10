<template>
  <div class="card">
    <div class="card-body">
      <div class="form-inline">
        <div class="form-group col-4">
          <multi-select
            v-model="rowData.method"
            :placeholder="$t('Select')"
            :show-labels="false"
            :options="methodOptions"
            :allow-empty="false"
          >
          </multi-select>
        </div>
        <div class="form-group col-8">
          <b-form-input class="w-100" v-model="rowData.url" :placeholder="$t('Enter Url')"></b-form-input>
        </div>
      </div>
      <div class="form-group">
        <div class="col">
          <b-form-textarea v-model="rowData.description" :placeholder="$t('Enter description')">

          </b-form-textarea>
        </div>
      </div>
      <div class="form-group">
        <div class="col">
          <b-form-textarea v-model="rowData.description" :placeholder="$t('Enter description')">

          </b-form-textarea>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
  import Vue from "vue";
  import MultiSelect from "vue-multiselect"

  Vue.component('multi-select', MultiSelect);

  const methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

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
        methodOptions: methods,
      };
    },
    computed: {

    },
    mounted() {

    },
    methods: {

    },
  }

</script>
