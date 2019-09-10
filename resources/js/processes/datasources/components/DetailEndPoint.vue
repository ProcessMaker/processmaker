<template>
  <div class="card">
    <div class="card-body">
      <div class="form-group col-12">
        <label for="purpose"> {{ $t('Purpose') }}</label>
        <b-form-input name="purpose" class="w-100" v-model="rowData.purpose"></b-form-input>
      </div>
      <div class="form-inline mb-2">
        <div class="form-group col-4">
          <label for="method">{{ $t('Method') }}</label>
          <multi-select
            name="method"
            v-model="rowData.method"
            :placeholder="$t('Select')"
            :show-labels="false"
            :options="methodOptions"
            :allow-empty="false"
          >
          </multi-select>
        </div>
        <div class="form-group col-8">
          <label for="url"> {{ $t('Url') }}</label>
          <b-form-input name="url" class="w-100" v-model="rowData.url"></b-form-input>
        </div>
      </div>
      <div class="form-group">
        <div class="col">
          <label>{{$t('Description')}}</label>
          <b-form-textarea v-model="rowData.description" :placeholder="$t('Enter description')">
          </b-form-textarea>
        </div>
      </div>
      <div class="form-group">
        <div class="col">
        <label>{{$t('Body')}}</label>
          <b-form-textarea v-model="rowData.body">
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
