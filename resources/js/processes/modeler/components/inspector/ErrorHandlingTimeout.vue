<template>
  <div role="group">
    <label for="timeout">{{ $t('Timeout') }}</label>
    <b-form-input id="timeout" type="number" min="0" max="3600" v-model="value" @input="userHasUpdatedValue"></b-form-input>
    <small class="form-text text-muted">{{ helper }}</small>
  </div>
</template>

<script>
import ErrorHandlingMixin from './ErrorHandlingMixin.vue';

export default {
  mixins: [ErrorHandlingMixin],
  data() {
    return {
      configKey: 'timeout',
      scriptHelperText: 'Set maximum run time in seconds. Leave empty to use script default. Set to 0 for no timeout.',
      dataConnectorHelperText: 'Set maximum run time in seconds. Leave empty to use data connector default. Set to 0 for no timeout.'
    }
  },
}
</script>

<style scoped></style>