<script setup>
import { defineProps, onMounted, onBeforeUnmount, ref, watch, defineEmits, getCurrentInstance } from 'vue';
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
const lastSearchTerm = ref('');
let requestSequence = 0;

watch(value, () => {
  emit('input', value.value);
});

watch(() => props.value, () => {
  value.value = props.value;
});

const loadOptions = (filter = '') => {
  const requestId = ++requestSequence;
  const remoteFilter = typeof filter === 'string' ? filter : '';

  loading.value = true;
  const params = { ...props.queryParams };
  if (props.remoteSearch) {
    lastSearchTerm.value = remoteFilter;
    params.filter = remoteFilter;
  }
  globalThis.ProcessMaker.apiClient.get(props.url, { params }).then((response) => {
    if (requestId === requestSequence) {
      options.value = response.data.data;
    }
  }).finally(() => {
    if (requestId === requestSequence) {
      loading.value = false;
    }
  });
};

const debouncedLoadOptions = debounce(loadOptions, 300);

const search = (filter) => {
  if (props.remoteSearch) {
    const remoteFilter = typeof filter === 'string' ? filter : '';
    lastSearchTerm.value = remoteFilter;
    debouncedLoadOptions(remoteFilter);
  }
};

const handleOpen = () => {
  if (!props.remoteSearch) {
    loadOptions();
    return;
  }

  if (options.value.length === 0) {
    loadOptions(lastSearchTerm.value);
  }
};

onMounted(() => {
  loadOptions();
});

onBeforeUnmount(() => {
  debouncedLoadOptions.cancel();
});
</script>

<template>
  <div>
    <multiselect v-model="value" :deselect-label="vue.$t('Can\'t remove this value')" :track-by="props.valueField" :label="props.textField"
                 :placeholder="vue.$t('Type here to search')" :options="options" :searchable="true" :allow-empty="false"
                 :multiple="true" :loading="loading" :internal-search="!props.remoteSearch" @open="handleOpen" @search-change="search">
      <template slot="noResult">
        {{ vue.$t('No elements found. Consider changing the search query.') }}
      </template>
      <template slot="noOptions">
        {{ vue.$t('No Data Available') }}
      </template>
    </multiselect>
  </div>
</template>
