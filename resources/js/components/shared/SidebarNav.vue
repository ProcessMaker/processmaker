<template>
  <div class="sidebar-nav">
    <ul v-for="page in pages">
      <li>
        <sidebar-button
          parent
          :active="page.active"
          v-on="$listeners"
        >
          {{ page.linkText }}
        </sidebar-button>
        <ul>
          <li v-for="subpage in page.pages">
            <sidebar-button
              :icon="subpage.icon"
              :active="subpage.active"
              v-on="$listeners"
            >
              {{ subpage.linkText }}
            </sidebar-button>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</template>

<script>
import SidebarButton from "./SidebarButton.vue";

export default {
  components: { SidebarButton },
  props: {
    pages: {
      type: Array,
      default: [],
    },
    active: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      showChildren: true,
    };
  },
  mounted() {
  },
  methods: {
    // changeView() {
    //     this.$emit('changeView');
    // },
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
