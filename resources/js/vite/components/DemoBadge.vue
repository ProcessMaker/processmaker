<template>
  <div class="vite-demo-badge">
    <span class="vite-demo-badge__label">{{ label }}</span>
    <span class="vite-demo-badge__meta">built with Vite</span>
  </div>
</template>

<script>
export default {
  name: 'DemoBadge',
  props: {
    label: {
      type: String,
      default: 'Demo',
    },
  },
};
</script>

<style scoped>
.vite-demo-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.4rem 0.75rem;
  border: 1px solid rgba(15, 23, 42, 0.12);
  border-radius: 0.35rem;
  background: rgba(255, 255, 255, 0.7);
  font-size: 0.875rem;
}

.vite-demo-badge__label {
  font-weight: 600;
  color: #0f172a;
}

.vite-demo-badge__meta {
  color: #475569;
}
</style>
