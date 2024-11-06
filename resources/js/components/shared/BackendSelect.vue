<script setup>
import { defineProps, onMounted, ref, watch, defineEmits, getCurrentInstance } from 'vue';

const vue = getCurrentInstance().proxy;
const options = ref([]);
const value = ref(props.value);
const emit = defineEmits(['input']);

// get component props
const props = defineProps({
  url: {
    type: String,
    required: true
  },
  valueField: {
    type: String,
    required: true
  },
  textField: {
    type: String,
    required: true
  },
  value: {
    type: Array,
  }
});


watch(value, () => {
  emit('input', value.value);
});

onMounted(() => {
  window.ProcessMaker.apiClient.get(props.url).then((response) => {
    options.value = response.data.data;
  });
})
</script>

<template>
  <div>
    <multiselect v-model="value" :deselect-label="vue.$t('Can\'t remove this value')" :track-by="props.valueField" :label="props.textField"
                 :placeholder="vue.$t('Type here to search')" :options="options" :searchable="true" :allow-empty="false" 
                 :multiple="true">
    </multiselect>
  </div>
</template>
