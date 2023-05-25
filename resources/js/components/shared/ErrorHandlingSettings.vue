<template>
  <b-form-group
    :label="$t('Timeout')"
    :description="$t('How many seconds the script should be allowed to run (0 is unlimited).')"
    :invalid-feedback="error"
    :state="state"
  >
    <div class="d-flex align-items-center">
      <b-form-input type="number" v-model="configTimeout" class="w-25" :min="0" :state="state"></b-form-input>
      <b-form-input type="range" min="0" max="300" v-model="configTimeout" :state="state" class="ml-3"></b-form-input>
    </div>
  </b-form-group>
</template>

<script>
export default {
  props: ['type', 'error', 'timeout'],
  data() {
    return {
    }
  },
  computed: {
    configTimeout: {
      get() {
        return this.timeout || 0;
      },
      set(value) {
        this.$emit('update:timeout', value);
      },
    },
    state() {
      return this.error ? false : null;
    }
  },
}
</script>