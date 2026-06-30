<script setup>
import { defineProps, onMounted, ref, watch, defineEmits, getCurrentInstance } from 'vue';
import debounce from 'lodash/debounce';

const vue = getCurrentInstance().proxy;
const options = ref([]);
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
    type: [Array, Object],
    default: null
  },
  queryParams: {
    type: Object,
    default: () => ({})
  },
  remoteSearch: {
    type: Boolean,
    default: false
  },
});

const value = ref(props.value);
const loading = ref(false);

watch(value, () => {
  emit('input', value.value);
});

watch(() => props.value, () => {
  value.value = props.value;
});

const loadOptions = (filter = '') => {
  loading.value = true;
  const params = { ...props.queryParams };
  if (props.remoteSearch) {
    params.filter = typeof filter === 'string' ? filter : '';
  }
  globalThis.ProcessMaker.apiClient.get(props.url, { params }).then((response) => {
    options.value = response.data.data;
  }).finally(() => {
    loading.value = false;
  });
};

const debouncedLoadOptions = debounce(loadOptions, 300);

const search = (filter) => {
  if (props.remoteSearch) {
    debouncedLoadOptions(filter);
  }
};

onMounted(() => {
  loadOptions();
});
</script>

<template>
  <div>
    <multiselect v-model="value" :deselect-label="vue.$t('Can\'t remove this value')" :track-by="props.valueField" :label="props.textField"
                 :placeholder="vue.$t('Type here to search')" :options="options" :searchable="true" :allow-empty="false"
                 :multiple="true" :loading="loading" :internal-search="!props.remoteSearch" @open="loadOptions" @search-change="search">
      <template slot="noResult">
        {{ vue.$t('No elements found. Consider changing the search query.') }}
      </template>
      <template slot="noOptions">
        {{ vue.$t('No Data Available') }}
      </template>
    </multiselect>
  </div>
</template>
