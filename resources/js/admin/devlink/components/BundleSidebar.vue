<template>
  <div class="sidebar">
    <div
      class="sidebar-item"
      :class="{ active: modelValue === 'summary' }"
      @click="$emit('type-change', 'summary')"
    >
      {{ $t('Summary') }}
    </div>
    <div
      v-for="item in assetTypes"
      :key="item.type"
      class="sidebar-item"
      :class="{ active: modelValue === item.type }"
      @click="$emit('type-change', item.type)"
    >
      {{ $t(item.name) }}
    </div>
  </div>
</template>

<script setup>
import assetTypes from "./assetTypes";

defineProps({
  modelValue: {
    type: String,
    required: true,
  },
});

defineEmits(['update:modelValue']);

</script>

<style lang="scss" scoped>
.sidebar {
  width: 250px;
  border-right: 1px solid #e0e0e0;
  padding: 20px;
}

.sidebar-item {
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 400;
  cursor: pointer;
  color: #596372;

  &:hover {
    background-color: #EAF2FF;
    border-radius: 8px;
  }

  &.active {
    background-color: #EAF2FF;
    color: #2773F3;
    border-radius: 8px;
  }
}
</style>
