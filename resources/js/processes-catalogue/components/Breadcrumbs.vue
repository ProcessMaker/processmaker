<template>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-transparent p-0 mb-4">
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
          href="/processes-catalogue"
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
