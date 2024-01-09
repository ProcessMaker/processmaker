<template>
  <b-form-select v-model="input" 
                 :options="formatRange"
                 multiple
                 size="sm">
  </b-form-select>
</template>

<script>
  export default {
    props: [
      "value", "formatRange"
    ],
    data() {
      return {
        input: []
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
