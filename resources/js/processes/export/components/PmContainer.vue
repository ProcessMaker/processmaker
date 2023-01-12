<template>
  <div>
    <router-link to="/processes/nav#0">First</router-link>
    <p>Testing</p>
    <router-view />
    slot: <slot />
  </div>
</template>

<script>
export default {
  components: {
  },
  mixins: [],
  props: [],
  data() {
    return {
    };
  },
  computed: {
    pages() {
      let children = this.$children.filter((child) => child.$options._componentTag === "pm-content");
      return children.map((contentComponent, i) => {
        const Bar = { template: '<div>bar</div>' }
        let route = { path: "/processes/nav#{i}", component: Bar };
        return { route };
      });
    },
  },
  beforeMount() {
    // console.log('Pages', this.pages);
    // this.pages.forEach((page) => this.$router.addRoute(page.route));
    const Bar = { template: '<div>bar</div>' }
    this.$router.addRoute({ path: '/processes/nav#0', component: Bar });
  },
};
</script>
