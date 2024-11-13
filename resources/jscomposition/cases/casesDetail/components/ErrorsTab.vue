<template>
  <RequestErrorsVue
    class="tw-w-full"
    :errors="errors"
  />
</template>

<script setup>
import { onMounted, ref } from "vue";
import RequestErrorsVue from "../../../../js/requests/components/RequestErrors.vue";
import { getErrors } from "../variables/index";

const errors = ref([]);

onMounted(() => {
  errors.value = getErrors();
});

</script>
