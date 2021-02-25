<template>
  <span>
    <b-form-checkbox @input="onChange(input)" v-model="input" switch></b-form-checkbox>
  </span>
</template>

<script>
export default {
  props: ['value'],
  data() {
    return {
      input: this.value,
    };
  },
  watch: {
    value: {
      handler: function(value) {
        this.input = value;
      },
    }
  },
  methods: {
    onChange(value) {
      this.$emit('change', value);
      this.$emit('input', value);
    },
  },
  mounted() {
    this.input = this.value;
  }
};
</script>
