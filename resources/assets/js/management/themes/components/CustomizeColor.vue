<template>
  <div id="app">
    <chrome-picker v-model="colors" />
  </div>
</template>

<script>
import { Chrome } from "vue-color";

export default {
  name: "app",
  components: {
    // HelloWorld,
    "chrome-picker": Chrome
  },
  data() {
    return {
      colors: {
        hex: "#3397e1"
      }
    };
  }
};
</script>