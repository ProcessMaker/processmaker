<template>
  <div>
    <span class="text-lg font-bold mb-2">
      {{ $t("Stage Aggregation") }}
    </span>
    <p class="text-sm mb-4">
      {{ $t("You can display an aggregation of a variable across the stages") }}
    </p>
    <label>{{ $t("Variable Name") }}</label>
    <input
      class="form-control"
      type="text"
      :placeholder="$t('Total Amount')"
      :value="agregationVariable"
      @input="saveVariable"
    >
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { debounce } from "lodash";

const agregationVariable = ref("");
const processId = ref(window.ProcessMaker?.modeler?.process?.id);

const saveVariable = debounce((event) => {
  ProcessMaker.apiClient
    .post(`processes/${processId.value}/aggregation`, {
      aggregation: event.target.value,
    })
    .then(() => { });
}, 1000);

onMounted(() => {
  ProcessMaker.apiClient
    .get(`/processes/${processId.value}/aggregation`)
    .then((response) => {
      agregationVariable.value = response.data.data;
    });
});

</script>
