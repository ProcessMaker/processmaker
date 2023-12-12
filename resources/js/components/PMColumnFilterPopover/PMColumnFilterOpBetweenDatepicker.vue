<template>
  <div>
    <b-form-datepicker v-model="value1" 
                       size="sm"
                       label-help=""
                       boundary="window"
                       :hide-header="true"
                       :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }">
    </b-form-datepicker>
    <b-form-datepicker v-model="value2" 
                       size="sm"
                       label-help=""
                       boundary="window"
                       :hide-header="true"
                       :date-format-options="{ year: 'numeric', month: 'numeric', day: 'numeric' }">
    </b-form-datepicker>
  </div>
</template>

<script>
  export default {
    props: [
      "value"
    ],
    data() {
      return {
        value1: "",
        value2: ""
      };
    },
    watch: {
      value1() {
        this.emitInput();
      },
      value2() {
        this.emitInput();
      },
      value: {
        handler(newValue) {
          if (Array.isArray(newValue)) {
            this.value1 = newValue[0] || "";
            this.value2 = newValue[1] || "";
          }
        },
        immediate: true
      }
    },
    methods: {
      emitInput() {
        this.$emit("input", [this.value1, this.value2]);
      }
    }
  };
</script>

<style scoped>
</style>