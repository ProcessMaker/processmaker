<template>
  <div>
    <PMColumnFilterOpDatetime v-model="value1">
    </PMColumnFilterOpDatetime>
    <PMColumnFilterOpDatetime v-model="value2">
    </PMColumnFilterOpDatetime>
  </div>
</template>

<script>
  import PMColumnFilterOpDatetime from "./PMColumnFilterOpDatetime"

  export default {
    components: {
      PMColumnFilterOpDatetime
    },
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
