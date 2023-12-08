<template>
  <div class="container card p-3">
    <div class="row">
      <div
        ref="sidebar"
        class="col-sm-3 col-lg-3 border-right"
      >
        <sidebar-nav
          :sidenav="sidenav"
          :active="activeIndex"
          @navigate="onNavigate"
        />
      </div>
      <div
        ref="content"
        class="col-sm-9 col-lg-9"
      >
        <slot :activeIndex="activeIndex" />
      </div>
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

    sidenav: {
      type: Array,
      default: [],
    },
  },
  data() {
    return {
      activeIndex: 0,
      // pages: [],
    };
  },
  computed: {
    // pages() {
    //   const pages = [];
    //   this.$children.forEach(child => {
    //     if (child.$options._componentTag == 'container-page') {
    //       this.pages.push(child);
    //       if (child.active) {
    //         this.active = child;
    //       }
    //     }
    //   });
    //   return pages;
    // },
  },
  watch: {
    children() {
      console.log("Children changed", this.$children);
    },
  },
  mounted() {
    window.ProcessMaker.EventBus.$on("return-to-summary-click", () => {
      this.onNavigate(0);
    });

    window.ProcessMaker.EventBus.$on("group-details-click", (groupTypePlural) => {
      const index = this.sidenav.findIndex((element) => element.title === groupTypePlural);
      if (index) {
        this.onNavigate(index);
      }
    });
  },
  methods: {
    // linkButtonToPage() {
    //   console.log('okay');
    // },
    onNavigate(i) {
      this.activeIndex = i;
    },
    // goTo(pageIndex) {
    //   this.active.setToInactive();
    //   this.active = this.pages[pageIndex];
    //   this.active.setToActive();
    // },
  },
};
</script>

<style>

</style>
