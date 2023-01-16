<template>
  <div>
    <router-link to="/">home</router-link>
    <ul>
      <li v-for="(page, i) in pages" :key="i">
        <router-link :to="i.toString()">page {{ i }}</router-link>
      </li>
    </ul>

    <br /><br />
    <div>Router View: <router-view /></div>
    <br /><br />
    pages count: {{ pages.length }}
    <br>
    links count: {{ links.length }}
  </div>
</template>

<script>
export default {
  mixins: [],
  props: [],
  data() {
    return {
      pages: [],
      links: [],
    };
  },
  methods: {
    makeFunctionalComponent(slot) {
      return {
        functional: true,
        render: () => slot,
      };
    },
    makeTitle(slot) {
      return slot.data.attrs?.header || "N/A"
    },
    addRoutes() {
      this.pages.forEach((page, i) => {
        this.$router.addRoute({
          path: "/" + i,
          component: page.component,
        });
      });
    },
    makeComponent(slot) {
      return {
        render: function (createElement) {
          return createElement('div', slot)
        }
      }
    },
    parsePages() {
      const pages = [];
      const links = [];
      const content = [];
      let index = 0;

      this.$slots.default
        // .filter((slot) => slot.componentOptions?.tag === "pm-content")
        .forEach((slot) => {

          if (slot.componentOptions?.tag === "pm-content") {
            pages.push(this.makeComponent(slot));
          } else {
            content.push(this.makeComponent(slot));
          }

          // const removeFromChildren = [];
          // const subLinks = [];
          const pageContent = [];
          slot.componentOptions?.children
          //   .filter((slot) => slot.componentOptions?.tag === "pm-content")
            .forEach((subSlot) => {
              if(slot.componentOptions?.tag === "pm-content") {
                // handle subpage here
                // pages.push(this.makeComponent(subSlot));
              } else {
                pageContent.push(slot);
              }
          //     pages.push({
          //       component: this.makeFunctionalComponent(subSlot),
          //       index: index,
          //     });
          //     subLinks.push({
          //       index,
          //       title: this.makeTitle(subSlot),
          //       subLinks: [],
          //     })
          //     removeFromChildren.push(i);
              });
          // removeFromChildren.forEach(i => slot.componentOptions.children.splice(i, 1));


          // pages.push({
          //   component: this.makeFunctionalComponent(slot),
          //   index,
          // });
          // links.push({
          //   index,
          //   title: this.makeTitle(slot),
          //   subLinks: subLinks,
          // })

        });

      this.pages = pages;
      this.links = links;
    },
  },
  mounted() {
    // debugger;
    this.parsePages();
    this.addRoutes();
  },
};
</script>
