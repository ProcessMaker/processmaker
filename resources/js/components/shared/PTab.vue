<template>
  <div>
    <div
      v-if="active"
      class="container-page"
    >
      <!-- <h3 class="border-bottom">{{ header }}</h3>
    <h4>{{ subheader }}</h4> -->
      <slot />
    </div>
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
    },
  },
  data() {
    return {
      button: null,
      buttonText: null,
      isActive: false,
      pages: [],
    };
  },
  mounted() {
    this.setNavigationText();
    if (this.active) {
      this.setToActive();
    }
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
    },
  },
};
</script>

<style>

</style>
