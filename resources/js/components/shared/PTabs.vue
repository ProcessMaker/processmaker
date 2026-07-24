<template>
  <div class="container card p-3">
    <div class="row">
      <div class="col-sm-3 col-lg-3 border-right" ref="sidebar">
        <sidebar-nav @navigate="onNavigate" :sidenav="sidenav" :active="activeIndex"></sidebar-nav>
      </div>
      <div class="col-sm-9 col-lg-9" ref="content">
        <slot v-bind:activeIndex="activeIndex"></slot>
      </div>    
    </div>
  </div>
</template>

<script>
  import PTab from "./PTab";
  import SidebarNav from "./SidebarNav";
  
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
        window.ProcessMaker.EventBus.$on("return-to-summary-click", () => {
            this.onNavigate(0);
        });

        window.ProcessMaker.EventBus.$on("group-details-click", (groupTypePlural) => {
            const index = this.sidenav.findIndex((element) => element.title === groupTypePlural);
            if (index) {
                this.onNavigate(index);
            }
        });
    }
  };
</script>

<style>
  
</style>
