<template>
  <div class="configurations-section">
    <div class="configurations-section-title">
      {{ $t(title) }}
    </div>
    <div class="card-grid">
      <div v-for="config in configurations || []" :key="config.type" class="card">
        <div class="config-info">
          <div class="config-label">{{ $t(config.name) }}</div>
          <div class="config-status">{{ status(config) }}</div>
        </div>
        <div class="config-action">
          <button
            v-if="props.type"
            class="config-action-button"
            @click="$emit('open-settings-modal', {
              key: config.type,
              value: $event,
              settingId: isInSettings(config)?.id,
              type: props.type
            })"
          >
            <i class="fp-bpmn-data-connector" />
          </button>
          <b-form-checkbox
            :checked="!!isInSettings(config)"
            switch
            :disabled="props.disabled"
            @change="$emit('config-change', {
              key: config.type,
              value: $event,
              settingId: isInSettings(config)?.id,
              type: config.type === 'ui_settings' ? config.type : props.type
            })"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { getCurrentInstance } from 'vue';

const vue = getCurrentInstance().proxy;
const props = defineProps({
  configurations: {
    type: Array,
    required: true,
    default: () => []
  },
  values: {
    type: Array,
    required: true,
    default: () => []
  },
  title: {
    type: String,
    required: true
  },
  type: {
    type: String,
    required: false
  },
  disabled: {
    type: Boolean,
    required: false,
    default: false
  }
});

defineEmits(['config-change', 'open-settings-modal']);

const isInSettings = (type) => {
  return (props.values || []).find(value => value.setting === type.type);
};

const status = (type) => {
  const settingValue = (props.values || []).find(value => value.setting === type.type);

  if (!settingValue) return vue.$t('Not shared');
  if (settingValue.config === null) return vue.$t('All');
  return vue.$t('Shared');
};
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
