<template>
  <div class="container-page" v-show="active">
    <h3 class="border-bottom">{{ header }}</h3>
    <h4>{{ subheader }}</h4>
    <slot></slot>
  </div>
</template>

<script>
  export default {
    props: {
      header: {
        type: String,
        default: "Header",
      },
      subheader: {
        type: String,
        default: "Subheader",
      },
      linkText: {
        type: String,
        default: null,
      },
      slug: {
        type: String,
        default: "slug",
      },
      icon: {
        type: String,
        default: "file",
      },
      active: {
        type: Boolean,
        default: false,
      }
    },
    data() {
      return {
        parent: false,
        pages: [],
      };
    },
    methods: {
      setNavigationText() {
        if (!this.linkText) {
          this.linkText = this.header;
        }
      }
    },
    mounted() {
      this.setNavigationText();
    }
  };
</script>

<style>
  
</style>
