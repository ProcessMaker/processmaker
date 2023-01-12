<template>
  <div class="container card p-3">
    <div class="row">
      <div
        ref="sidebar"
        class="col-sm-3 col-lg-3 border-right"
      >
        <sidebar-nav
          active
          :header="header"
          :link-text="linkText"
          :pages="pages"
          @changeView="hitHere()"
        />
      </div>
      <div
        ref="content"
        class="col-sm-9 col-lg-6"
      >
        ptabs slot: <slot />
      </div>
      <div class="col-sm-0 col-lg-3" />
    </div>
  </div>
</template>

<script>
import PTab from "./PTab.vue";
import SidebarNav from "./SidebarNav.vue";

export default {
  components: { PTab, SidebarNav },
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
  },
  data() {
    return {
      pages: [],
    };
  },
  mounted() {
    this.findPages();
  },
  methods: {
    hitHere() {
        console.log('The sidebar button emitted to the PTabs.');
    },
    findPages() {
      this.$children.forEach(child => {
        if (child.$options._componentTag == 'p-tab') {
        console.log('child', child);
          this.pages.push(child);
          child.$children.forEach(subchild => {
            if (subchild.$options._componentTag == 'p-tab') {
              child.parent = true;
              // this.pages.push(subchild);
              console.log('subchild', subchild);
              child.pages.push(subchild);
            }
          });
        }
      });
    },
  },
};
</script>

<style>

</style>
