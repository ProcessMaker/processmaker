<template>
  <div v-show="vShow" class="pm-floating-buttons">
    <slot name="content">
    </slot>
  </div>
</template>

<script>
  export default {
    data() {
      return {
        vShow: false
      };
    },
    mounted() {
      let parent = this.$parent.$el;
      parent.style.position = "relative";
    },
    methods: {
      show() {
        this.vShow = true;
      },
      close() {
        this.vShow = false;
      }
    }
  };
</script>

<style scoped>
  .pm-floating-buttons {
    display: flex;
    position: absolute;
    top: 50%;
    right: 0;
    transform: translateY(-50%);
    border-radius: 4px;
    padding: 4px;
    box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
    background-color: white;
    white-space: nowrap;
  }
</style>