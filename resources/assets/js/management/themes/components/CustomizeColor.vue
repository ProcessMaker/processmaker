<template>
  <div id="app">
    <chrome-picker v-model="colors" />
    <!-- <HelloWorld msg="Welcome to Your Vue.js App"/> -->
  </div>
</template>

<script>
// import HelloWorld from './components/HelloWorld.vue'

import { Chrome } from "vue-color";

var colors = { r: 255, g: 0, b: 0 };

export default {
  name: "app",
  components: {
    // HelloWorld,
    "chrome-picker": Chrome
  },
  data() {
    return {
      colors: {
        hex: "#194d33",
        hsl: {
          h: 150,
          s: 0.5,
          l: 0.2,
          a: 1
        },
        hsv: {
          h: 150,
          s: 0.66,
          v: 0.3,
          a: 1
        },
        rgba: {
          r: 25,
          g: 77,
          b: 51,
          a: 1
        },
        a: 1
      }
    };
  }
};
</script>