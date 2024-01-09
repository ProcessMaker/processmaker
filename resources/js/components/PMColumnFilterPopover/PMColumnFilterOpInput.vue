<template>
  <b-form-input v-model="input" 
                :placeholder="$t('Type value')"
                size="sm">
  </b-form-input>
</template>

<script>
  export default {
    props: [
      "value"
    ],
    data() {
      return {
        input: ""
      };
    },
    watch: {
      value: {
        handler(newValue) {
          this.input = newValue;
        },
        immediate: true
      },
      input() {
        this.$emit("input", this.input);
      }
    }
  };
</script>

<style scoped>
</style>
