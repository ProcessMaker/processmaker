<template>
  <div class="sidebar">
    <div
      class="sidebar-item"
      :class="{ active: modelValue === 'summary' }"
      @click="$emit('update:modelValue', 'summary')"
    >
      {{ $t('Summary') }}
    </div>
    <div
      v-for="item in assetTypes"
      :key="item.type"
      class="sidebar-item"
      :class="{ active: modelValue === item.type }"
      @click="$emit('update:modelValue', item.type)"
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
  background: white;
  border-right: 1px solid #e0e0e0;
  padding: 20px 0;
}

.sidebar-item {
  padding: 10px 20px;
  font-size: 14px;
  font-weight: 400;
  cursor: pointer;
  color: #596372;

  &:hover {
    background: #EAF2FF;
  }

  &.active {
    background: #EAF2FF;
    color: #2773F3;
  }
}
</style>
