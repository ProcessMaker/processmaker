<template>
  <div class="configurations-section">
    <h3>{{ $t('Platform Configurations') }}</h3>
    <div class="config-grid">
      <div v-for="config in configurations" :key="config.key" class="config-row">
        <div class="config-info">
          <span>{{ $t(config.label) }}</span>
          <span class="config-status">{{ config.status }}</span>
        </div>
        <b-form-checkbox 
          v-model="config.enabled"
          switch
          @change="$emit('config-change', { key: config.key, value: $event })"
        ></b-form-checkbox>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  configurations: {
    type: Array,
    required: true
  }
});

defineEmits(['config-change']);
</script>

<style lang="scss" scoped>
// ... estilos de configuraciones
</style>
