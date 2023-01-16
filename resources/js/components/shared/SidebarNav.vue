<template>
  <div class="sidebar-nav">
    <ul v-for="page in pages">
      <li>
        <sidebar-button :page="page" @click="onClick">{{ page.buttonText }}</sidebar-button>
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
      onClick(button) {
        this.$emit('navigate', button);
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
