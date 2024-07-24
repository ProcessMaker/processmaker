<template>
  <b-row align-v="start">
      <b-col class="col-1 p-0 pr-1 text-right">
        <i class="fas fa-exclamation-triangle text-warning" aria-hidden="true"></i>
        <span class="sr-only">Warning</span>
      </b-col>
      <b-col class="p-0 pl-1">
        <h5 class="mb-3 fw-semibold">
          {{ title }}
          <div>
            <small class="helper text-muted">{{ helperText }}</small>
          </div>
        </h5>
      </b-col>
    </b-row>
</template>


<script>
export default {
  name:'WarningMessage',
  props: {
    title: {
      type: String,
      default: ''
    },
    helperText: {
      type: String,
      default: ''
    }
  },
 
}
</script>
