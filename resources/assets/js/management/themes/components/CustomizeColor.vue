<template>
  <div id="app">
    <chrome-picker v-model="colors"/>
  </div>
</template>

<script>
import { Chrome } from "vue-color";

export default {
  name: "app",
  props: ["value"],
  components: {
    // HelloWorld,
    "chrome-picker": Chrome
  },
  computed: {
    colors: {
      get() {
        return this.value;
      },
      set(v) {
        this.$emit("input", v.hex);
      }
    }
  }
};
</script>