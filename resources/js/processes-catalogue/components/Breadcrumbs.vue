<template>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-3">
      <li class="breadcrumb-item">
        <a href="/" :aria-label="$t('Home')">
          <i class="fas fa-home" />
        </a>
      </li>

      <li class="breadcrumb-item">
        <a href="/processes-catalogue" :aria-label="$t('Home')">
          {{ $t('Processes') }}
        </a>
      </li>

      <li v-if="category" class="breadcrumb-item">
        <a :href="caegoryRoute" :aria-label="category">
          {{ category }}
        </a>
      </li>

      <li v-if="process" class="breadcrumb-item">
        {{ process }}
      </li>
    </ol>
  </nav>
</template>

<script>
export default {
  router: window.ProcessMaker.Router,
  props: ["process", "caegoryRoute", "category"],
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
};
</script>
