<template>
  <div class="sidebar-nav">
    <ul v-for="page in pages">
      <li>
        <sidebar-button parent :active="page.active">{{ page.linkText }}</sidebar-button>
        <ul>
          <li v-for="subpage in page.pages">
            <sidebar-button :icon="subpage.icon" :active="subpage.active">{{ subpage.linkText }}</sidebar-button>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</template>

<script>
  import SidebarButton from "./SidebarButton";  
    
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

/* .sidebar-nav-container {
    padding-left: 0;
}

.process-name-container {
  text-align: left;
  width: 100%;
}

a.sidebar-nav {
  color: #42526E;
}

li {
  list-style: none;
  text-align: left;
  width: 100%;
  &:hover {
    background-color: #f7f9fb;
  }
}

.active {
    background-color: $grey-bg;
    border-radius: 0.125em;
}

.active::before {
  position: relative;
  left: 0;
  width: 4px;
  height: 24px;
  content: "";
  background-color: #0872c2;
  border-radius: 6px;
} */

</style>
