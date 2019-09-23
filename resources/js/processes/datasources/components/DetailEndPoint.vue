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


      <div class="form-group col-12">
          <div class="row">
            <div class="col">
              <label>{{ $t('Headers') }}</label>
            </div>
            <div class="col-8">
              <button type="button" href="#" @click="addHeader" id="add_header"
                      class="btn btn-secondary btn-sm float-right">
                <i class="fas fa-plus"></i> {{ $t('Add') }}
              </button>
            </div>
          </div>

          <list-headers
            ref="headersListing"
            :headers="rowData.headers || []">
          </list-headers>
      </div>

      <div class="form-group">
        <div class="col">
          <label>{{ $t('Body') }}</label>
          <b-form-textarea v-model="rowData.body">
          </b-form-textarea>
        </div>
      </div>

      <div class="form-group">
        <div class="col">
          <label>{{ $t('Sample Data') }}</label>
        </div>
        <b-col cols="12" style="height:12em">
          <monaco-editor :options="monacoOptions" v-model="testData" language="json" class="h-100 border" />
        </b-col>
        <small v-if="testError" class="text-danger">{{ testError }}</small>
      </div>

      <div class="form-group">
        <div class="col">
          <label>{{ $t('Response') }}</label>
        </div>
        <b-col cols="12" style="height:12em" class="overflow-auto">
          <code class="border">{{ testResponse }}</code>
        </b-col>
      </div>

    </div>
  </div>
</template>

<script>
  import Vue from "vue";
  import MultiSelect from "vue-multiselect"
  import ListHeaders from "./ListHeaders";
  import CanTestEndPoint from "./CanTestEndPoint";

  Vue.component('multi-select', MultiSelect);
  Vue.component('list-headers', ListHeaders);

  const methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

  export default {
    inheritAttrs: false,
    mixins: [CanTestEndPoint],
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
    methods: {
      addHeader() {
        let header = {
          id: this.$refs.headersListing.headers.length,
          view: false,
          key: "",
          value: "",
          description: ""
        };
        this.$refs.headersListing.headers.push(header);
        this.$refs.headersListing.fetch();
        this.$refs.headersListing.detail(header);
        this.$set(this.rowData, 'headers', this.$refs.headersListing.headers);
      }
    }
  }

</script>
