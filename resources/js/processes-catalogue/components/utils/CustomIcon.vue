<template>
    <svg
      class="icon"
      viewBox="0 0 17 17"
    >
      <path :d="path" :fill="color"/>
    </svg>
  </template>
  
  <script>
  import icons from './icons.json';
  
  export default {
    name: 'CustomIcon',
    props: {
      name: String,
      color: String,
    },
    computed: {
      path() {
        return icons[this.name];
      },
    },
  };
  </script>
  <style lang="scss" scoped>
  .icon {
    width:16px;
    height:16px
  }
  
  </style>
