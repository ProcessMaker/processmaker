<template>
  <div role="group">
    <label for="retry_attempts">{{ $t('Retry Attempts') }}</label>
    <b-form-input id="retry_attempts" type="number" v-model="value" @input="userHasUpdatedValue" min="0" max="50"></b-form-input>
    <small class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>
import ErrorHandlingMixin from './ErrorHandlingMixin.vue';

export default {
  mixins: [ErrorHandlingMixin],
  data() {
    return {
      configKey: 'retry_attempts',
      scriptHelperText: 'Set maximum run retry attempts in seconds. Leave empty to use script default. Set to 0 for no retry attempts.',
      dataConnectorHelperText: 'Set maximum run retry attempts in seconds. Leave empty to use data connector default. Set to 0 for no retry attempts.'
    }
  },
}
</script>

<style scoped></style>
