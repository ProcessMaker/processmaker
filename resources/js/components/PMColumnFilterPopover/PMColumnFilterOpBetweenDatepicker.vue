<template>
  <div>
    <PMColumnFilterOpDatetime v-model="value1" class="pm-column-filter-op-between-datetime">
    </PMColumnFilterOpDatetime>
    <PMColumnFilterOpDatetime v-model="value2" class="pm-column-filter-op-between-datetime">
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
  .pm-column-filter-op-between-datetime{
    margin-bottom: 5px;
  }
</style>
