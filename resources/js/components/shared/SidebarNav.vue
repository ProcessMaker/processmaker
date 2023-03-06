<template>
  <div class="sidebar-nav">
    <ul
      v-for="(page, i) in sidenav"
      :key="i"
      :class="i > 0 ? 'mb-0' : 'mb-2'"
    >
      <div v-if="i === 0">
        <li>
          <sidebar-button
            :parent="i === 0"
            :active="i === active"
            :icon="page.icon"
            :collapsable="collapsable"
            class="text-capitalize"
            @click="onClick(i)"
            @toggleClick="toggleChildren"
          >
            {{ formatAssetName(page.title) }}
          </sidebar-button>
        </li>
      </div>
      <div v-else>
        <b-collapse v-model="showChildren">
          <li v-if="!page.hidden">
            <sidebar-button
              :parent="i === 0"
              :child="i + 1"
              :active="i === active"
              :icon="page.icon"
              class="text-capitalize"
              @click="onClick(i)"
            >
              {{ formatAssetName(page.title) }}
            </sidebar-button>
          </li>
        </b-collapse>
      </div>
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
    },
    collapsable: {
      type: Boolean,
      default: true,
    },
  },
  data() {
    return {
      showChildren: true,
    };
  },
  mounted() {
    // console.log('pages', this.$children);
  },
  methods: {
    onClick(i) {
      this.$emit("navigate", i);
    },
    formatAssetName(string) {
      if (!string) {
        return;
      }

      const newString = string.replace(/([A-Z])/g, " $1");
      return newString;
    },
    toggleChildren() {
      this.showChildren = !this.showChildren;
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
