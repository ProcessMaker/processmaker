<template>
  <div class="card-grid">
    <div v-for="(type, index) in assetTypes" :key="index" class="card">
      <!-- Icon -->
      <div class="icon-container">
        <i :class="type.icon"></i>
      </div>
      <!-- Content -->
      <div class="content">
        <h3>{{ $t(type.name) }}</h3>
        <span class="asset-count">{{ getAssetCount(type.type) }} items</span>
      </div>
      <!-- Button -->
      <div class="button-container">
        <button @click.prevent="navigate(type)" class="view-button">
          {{ $t('View') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router/composables';
import assetTypes from './assetTypes';

const router = useRouter();

const props = defineProps({
  assets: {
    type: Array,
  },
});

defineEmits(['view']);

const getAssetCount = (type) => {
  return props.assets?.filter(asset => asset.type.toUpperCase() === type.toUpperCase()).length;
};

const navigate = (type) => {
  router.push({ 
    name: 'bundle-asset-listing', 
    params: { type: type.type },
    state: { assets: props.assets.filter(asset => 
      asset.type.toUpperCase() === type.type.toUpperCase()
    )}
  });
};
</script>

<style lang="scss" scoped>
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(387px, 1fr));
  gap: 8px;
  padding: 24px;
}

.card {
  display: flex;
  flex-direction: unset;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-radius: 12px;
  border: 1px solid #E9ECF1;
}

.icon-container {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 58px;
  height: 58px;
  background-color: #F3F5F7;
  border-radius: 50%;
}

.icon {
  width: 24px;
  height: 24px;
}

.content {
  flex-grow: 1;
  margin-left: 15px;
}

.asset-count {
  font-size: 14px;
  font-weight: 400;
  color: #728092;
}

h3 {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  color: #20242A;
}

p {
  margin: 0;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
  color: #728092;
}

.button-container {
  display: flex;
  align-items: center;
}

.view-button {
  background-color: white;
  color: #20242A;
  font-weight: 500;
  padding: 6px 12px;
  border: 1px solid #D7DDE5;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  cursor: pointer;
  transition: background-color 0.3s;
}

.view-button:hover {
  background-color: #f1f3f5;
}
</style>
