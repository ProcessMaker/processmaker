<script setup>
import { defineProps, onMounted, ref, watch, defineEmits } from 'vue';

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
    type: Object,
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
    <multiselect v-model="value" deselect-label="Can't remove this value" :track-by="props.valueField" :label="props.textField"
      placeholder="Select one" :options="options" :searchable="false" :allow-empty="false">
    </multiselect>
  </div>
</template>
