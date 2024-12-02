<template>
  <div class="configurations-section">
    <div class="configurations-section-title">
      {{ $t(title) }}
    </div>
    <div class="card-grid">
      <div v-for="config in configurations" :key="config.type" class="card">
        <div class="config-info">
          <div class="config-label">{{ $t(config.name) }}</div>
          <div class="config-status">{{ config.status }}</div>
        </div>
        <div class="config-action">
          <button class="config-action-button">
            <i class="fp-bpmn-data-connector"></i>
          </button>
          <b-form-checkbox
            v-model="config.enabled"
            switch
            @change="$emit('config-change', { key: config.type, value: $event })"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>

const props = defineProps({
  configurations: {
    type: Array,
    required: true
  },
  title: {
    type: String,
    required: true
  }
});

defineEmits(['config-change']);
</script>

<style lang="scss" scoped>
.card-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(387px, 1fr));
  gap: 8px;
  padding: 24px;
}
.configurations-section-title {
  font-size: 18px;
  font-weight: 500;
  padding-left: 24px;
  padding-right: 24px;
  margin-bottom: 8px;
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
.config-label {
  margin: 0;
  font-size: 14px;
  font-weight: 500;
  line-height: 20px;
  color: #20242A;
}
.config-status {
  margin: 0;
  font-size: 14px;
  font-weight: 400;
  line-height: 20px;
  color: #728092;
}
.config-action {
  display: flex;
  align-items: center;
  gap: 8px;
}
.config-action-button {
  background-color: transparent;
  border: none;
  cursor: pointer;
}
</style>
