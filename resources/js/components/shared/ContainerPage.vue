<template>
  <div class="container-page" v-if="isActive">
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
      },
      parent: {
        type: Boolean,
        default: false,
      }
    },
    data() {
      return {
        button: null,
        buttonText: null,
        isActive: false,
        pages: [],
      };
    },
    methods: {
      setNavigationText() {
        if (!this.linkText) {
          this.buttonText = this.header;
        } else {
          this.buttonText = this.linkText;
        }
      },
      setToActive() {
        if (this.button) {
          this.button.setToActive();
        }
        this.isActive = true;
      },
      setToInactive() {
        if (this.button) {
          this.button.setToInactive();
        }
        this.isActive = false;
      }
    },
    mounted() {
      this.setNavigationText();
      if (this.active) {
        this.setToActive();
      }
    }
  };
</script>

<style>
  
</style>
