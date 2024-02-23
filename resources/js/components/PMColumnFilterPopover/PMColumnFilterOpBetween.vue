<template>
  <div>
    <b-form-input v-model="value1" :placeholder="$t('input') + ' ' + 1" size="sm" class="pm-column-filter-op-between-input"></b-form-input>
    <b-form-input v-model="value2" :placeholder="$t('input') + ' ' + 2" size="sm" class="pm-column-filter-op-between-input"></b-form-input>
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
  .pm-column-filter-op-between-input{
    margin-bottom: 5px;
  }
</style>
