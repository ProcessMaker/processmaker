<template>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item">
        <a
          href="/"
          :aria-label="$t('Home')"
        ><i class="fas fa-home" /></a>
      </li>
      <li
        v-for="(route, index) in list"
        :key="index"
        class="breadcrumb-item"
        :class="{active: isActive(index)}"
        :role="isActive(index) ? 'heading' : null"
        :aria-level="isActive(index) ? '1' : null"
      >
        <router-link
          v-if="route.router"
          v-slot="{ href, navigate }"
          :to="route.link"
        >
          <a
            :href="href"
            @click="navigate"
          >{{ route.title }}</a>
        </router-link>
        <a
          v-else
          :href="route.link"
        >{{ route.title }}</a>
      </li>
    </ol>
  </nav>
</template>

<script>
export default {
  router: window.ProcessMaker.Router,
  props: ["routes"],
  data() {
    return {
      list: [],
      loading: false,
    };
  },
  mounted() {
    this.list = this.transform(this.routes);
  },
  methods: {
    isActive(index) {
      return index == (this.list.length - 1);
    },
    updateRoutes(routes) {
      this.list = routes;
    },
    transform(routes) {
      const list = [];
      Object.entries(routes).forEach(([title, link]) => {
        list.push({
          title,
          link,
          router: false,
        });
      });
      return list;
    },
  },
};
</script>
