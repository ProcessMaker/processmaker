<template>
  <div class="sidebar-nav">
    <ul v-for="(page, i) in sidenav" :key="i" class="mb-2">
      <li>
        <sidebar-button :parent="i === 0" :active="i === active" :icon="page.icon" @click="onClick(i)" class="text-capitalize">{{ formatAssetName(page.title) }}</sidebar-button>
      </li>
    </ul>
  </div>
</template>

<script>
import SidebarButton from "./SidebarButton";

export default {
  components: { SidebarButton },
  props: {
    sidenav: {
      type: Array,
      default: [],
    },
    active: {
      type: Number,
      default: 0,
    }
  },
  data() {
    return {
      showChildren: true,
    };
  },
  mounted() {
  },
  methods: {
    onClick(i) {
      this.$emit('navigate', i);
    },
    formatAssetName(string) {
      if (!string) {
        return;
      }

      const newString = string.replace(/([A-Z])/g, " $1");
      return newString;
    },
  },
};
</script>

<style lang="scss" scoped>
@import "../../../sass/variables";

ul {
  padding: 0;
}

li {
  list-style: none;
  width: 100%;
}
</style>
