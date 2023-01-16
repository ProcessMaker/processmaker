<template>
  <div class="container card p-3">
    <div class="row">
      <div class="col-sm-3 col-lg-3 border-right" ref="sidebar">
        <sidebar-nav @navigate="onNavigate" :header="header" :link-text="linkText" :pages="pages"></sidebar-nav>
      </div>
      <div class="col-sm-9 col-lg-6" ref="content">
        <slot></slot>
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
    },
    data() {
      return {
        active: null,
        pages: [],
      };
    },
    methods: {
      linkButtonToPage() {
        console.log('okay');
      },
      onNavigate(button) {
        this.active.setToInactive();
        this.active = button.page;
        this.active.setToActive();
      },
      findPages() {
        this.$children.forEach(child => {
          if (child.$options._componentTag == 'container-page') {
            this.pages.push(child);
            if (child.active) {
              this.active = child;
            }
          }
        });
      },
    },
    mounted() {
      this.findPages();
    }
  };
</script>

<style>
  
</style>
