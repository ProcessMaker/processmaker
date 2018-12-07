<template>
  <div>
    <i class="fas fa-download"></i>
    <a>Download {{file}}</a>
  </div>
</template>


<script>
export default {
  data() {
    return {
      file: "example.png"
    };
  },
  methods: {
    onClick() {
      console.log("HEllo");
    }
  }
};
</script>

<style lang="scss" scoped>
</style>