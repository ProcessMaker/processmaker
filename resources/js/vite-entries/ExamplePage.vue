<template>
  <div class="vite-example">
    <h2 class="vite-example__title">{{ title }}</h2>
    <p class="vite-example__message">{{ message }}</p>
    <p class="vite-example__hint">
      Edit this file and save — the UI updates without a full page reload (HMR).
    </p>
    <p class="vite-example__counter">Clicks: {{ count }}</p>
    <button type="button" class="btn btn-primary btn-sm" @click="count += 1">
      Increment
    </button>
  </div>
</template>

<script>
export default {
  name: 'ExamplePage',
  data() {
    return {
      title: 'Vite Example Page (Vue 2)',
      message: 'This page loads its script via Vite.!!!!!asdfasdADFSDF',
      count: 0,
    };
  },
};
</script>

<style scoped>
.vite-example {
  padding: 1rem;
  border: 5px solid #0d10d3;
  border-radius: 0.25rem;
  background: #f8f9fa;
}
.vite-example__title {
  margin-bottom: 0.5rem;
  font-size: 1.25rem;
}
.vite-example__message {
  margin-bottom: 0.5rem;
  color: #495057;
}
.vite-example__hint {
  margin: 0.75rem 0;
  font-size: 0.875rem;
  color: #6c757d;
}
.vite-example__counter {
  margin: 0.5rem 0;
  font-weight: 600;
}
</style>
