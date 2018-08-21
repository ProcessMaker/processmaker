<template>
  <div id="app">
    <chrome-picker v-model="colors" />
    <!-- <HelloWorld msg="Welcome to Your Vue.js App"/> -->
  </div>
</template>

<script>
// import HelloWorld from './components/HelloWorld.vue'

import { Chrome } from 'vue-color'

var colors = { r: 255, g: 0, b: 0 }

export default {
  name: 'app',
  components: {
    // HelloWorld,
    'chrome-picker': Chrome,
  },
  data () {
    return {
      colors
    }
  }
}
</script>