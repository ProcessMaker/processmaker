<template>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb border-top border-bottom">
            <li class="breadcrumb-item"><a href="/"><i class="fas fa-home"></i></a></li>
            <li v-for="(route, index) in list" class="breadcrumb-item" :class="{active: isActive(index)}" :key="index">
              <router-link v-if="route.router" :to="route.link" v-slot="{ href, navigate }">
                <a :href="href" @click="navigate">{{ route.title }}</a>
              </router-link>
              <a :href="route.link" v-else>{{ route.title }}</a>
            </li>
        </ol>
    </nav>
</template>

<script>
export default {
  router: window.ProcessMaker.Router,
  props: ['routes'],
  data() {
    return {
      list: [],
      loading: false,
    }
  },
  methods: {
    isActive(index) {
      return index == (this.list.length - 1);
    },
    updateRoutes(routes) {
      this.list = routes;
    },
    transform(routes) {
      let list = [];
      Object.entries(routes).forEach(([title, link]) => {
        list.push({
          title: title,
          link: link,
          router: false
        });
      });
      return list;
    }
  },
  mounted() {
    this.list = this.transform(this.routes);
  }
}
</script>
