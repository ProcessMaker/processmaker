<template>
  <nav class="breadcrum-main" aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent">
      <li class="breadcrumb-item">
        <a
          href="/"
          :aria-label="$t('Home')"
        >
          <i class="fas fa-home" />
        </a>
      </li>

      <li class="breadcrumb-item">
        <a
          href="/process-browser"
          :aria-label="$t('Home')"
        >
          {{ $t('Processes') }}
        </a>
      </li>

      <li
        v-if="category"
        class="breadcrumb-item"
      >
        <a :aria-label="category">
          {{ category }}
        </a>
      </li>

      <li
        v-if="process"
        class="breadcrumb-item"
      >
        {{ process }}
      </li>

      <li
        v-if="template"
        class="breadcrumb-item"
      >
        {{ template }}
      </li>
    </ol>
  </nav>
</template>

<script>
export default {
  router: window.ProcessMaker.Router,
  props: ["process", "category", "template"],
  data() {
    return {
    };
  },
  mounted() {
  },
  methods: {},
};
</script>

<style lang="css" scoped>
.breadcrum-main {
  margin: -16px -16px 0px -16px;
  background: white;
  border-bottom: 1px solid #DADDDF
}
</style>
