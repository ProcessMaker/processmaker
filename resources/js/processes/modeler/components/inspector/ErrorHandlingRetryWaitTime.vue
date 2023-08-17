<template>
  <div role="group">
    <label for="retry_wait_time">{{ $t('Retry Wait Time') }}</label>
    <b-form-input id="retry_wait_time" type="number" v-model="value" @input="userHasUpdatedValue" min="0" max="3600"></b-form-input>
    <small class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>
import ErrorHandlingMixin from './ErrorHandlingMixin';

export default {
  mixins: [ErrorHandlingMixin],
  data() {
    return {
      configKey: 'retry_wait_time',
      scriptHelperText: 'Set maximum run retry wait time in seconds. Leave empty to use script default. Set to 0 for no retry wait time.',
      dataConnectorHelperText: 'Set maximum run retry wait time in seconds. Leave empty to use data connector default. Set to 0 for no retry wait time.'
    }
  },
}
</script>

<style scoped></style>