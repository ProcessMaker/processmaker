<template>
  <div class="container card p-3">
    <div class="row">
      <div class="col-sm-3 col-lg-3 border-right" ref="sidebar">
        <sidebar-nav @navigate="onNavigate" :sidenav="sidenav" :active="activeIndex"></sidebar-nav>
      </div>
      <div class="col-sm-9 col-lg-6" ref="content">
        <slot v-bind:activeIndex="activeIndex"></slot>
      </div>
      <div class="col-sm-0 col-lg-3">
      </div>      
    </div>
  </div>
</template>

<script>
  import ContainerPage from "./ContainerPage";
  import SidebarNav from "./SidebarNav";
  
  export default {
    components: { ContainerPage, SidebarNav },
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
    methods: {
      // linkButtonToPage() {
      //   console.log('okay');
      // },
      onNavigate(i) {
        console.log("ON NAVIGATE Container.vue", i);
        this.activeIndex = i;
      },
      // goTo(pageIndex) {
      //   this.active.setToInactive();
      //   this.active = this.pages[pageIndex];
      //   this.active.setToActive();
      // },
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
      }
    },
    mounted() {
      // this.findPages();
      console.log("mounted children", this.$children);
    }
  };
</script>

<style>
  
</style>
