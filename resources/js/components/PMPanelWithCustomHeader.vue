<template>
  <div class="pm-panel-wch">
    <div class="pm-panel-wch-header d-flex flex-nowrap">
      <div class="flex-grow-0">
        <slot name="header-left-content"></slot>
      </div>
      <div class="flex-grow-1 d-flex align-items-center">
        <slot name="title"><b class="pm-panel-wch-title"> {{ title }} </b></slot>
      </div>
      <div class="flex-grow-0">
        <slot name="header-right-content"></slot>
      </div>
    </div>
    <div class="pm-panel-wch-content">
      <slot></slot>
    </div>
  </div>
</template>

<script>
  export default {
    components: {},
    props: {
      title: {
        type: String,
        default: "Header"
      }
    },
    data() {
      return {};
    },
    mounted() {},
    watch: {},
    methods: {}
  }
</script>

<style scoped>
  .pm-panel-wch {
    border: 1px solid #CDDDEE;
    border-radius: 5px;
    background: white;
  }
  .pm-panel-wch-header {
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
    background:
      linear-gradient(0deg, rgba(255, 255, 255, 0.72),
      rgba(255, 255, 255, 0.72)),
      #96C3F9;
    padding: 10px;
  }
  .pm-panel-wch-content {
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
    background: white;
    padding: 16px;
  }
  .pm-panel-wch-title {
    padding-top: 3px;
    padding-bottom: 3px;
  }
</style>