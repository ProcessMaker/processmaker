<template>
  <div class="card">
    <div class="card-body">
      <div class="form-group col-12">
        <label for="key"> {{ $t('Key') }}</label>
        <b-form-input name="key" class="w-100" v-model="rowData.key"></b-form-input>
      </div>
      <div class="form-group col-12">
        <label for="value"> {{ $t('Value') }}</label>
        <b-form-input name="value" class="w-100" v-model="rowData.value"></b-form-input>
      </div>
      <div class="form-group">
        <div class="col">
          <label>{{$t('Description')}}</label>
          <b-form-textarea v-model="rowData.description" :placeholder="$t('Enter description')">
          </b-form-textarea>
        </div>
      </div>
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
      },
    },
  }

</script>
