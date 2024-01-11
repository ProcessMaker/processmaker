<template>
  <b-form-radio-group v-model="input">
    <b-form-radio :value="true">{{$t("True")}}</b-form-radio>
    <b-form-radio :value="false">{{$t("False")}}</b-form-radio>
  </b-form-radio-group>
</template>

<script>
  export default {
    props: [
      "value"
    ],
    data() {
      return {
        input: false
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
