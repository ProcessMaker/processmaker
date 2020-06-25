<template>
  <div>
    <b-form-checkbox v-model="required">{{ $t('Required') }}</b-form-checkbox>
  </div>
</template>

<script>
export default {
  props: ['value'],
  data() {
    return {
      required: false
    };
  },
  watch: {
    value: {
      handler() {
        if (this.value && this.value === 'required') {
          this.required = true;
        } else {
          this.required = false;
        }
      },
      immediate: true,
    },
    required: {
      handler() {
        this.$emit('input', this.required ? 'required' : '');
      }
    }
  }
};
</script>