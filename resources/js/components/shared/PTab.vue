<template>
  <div
    v-show="active"
    class="p-tab"
  >
    <h3 class="border-bottom">
      Exporting: {{ header }}
    </h3>
    <h4>{{ subheader }}</h4>
    p-tab slot: <slot />
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
  mounted() {
    this.setNavigationText();
  },
  methods: {
    setNavigationText() {
      if (!this.linkText) {
        this.linkText = this.header;
      }
    },
  },
};
</script>

<style>

</style>
