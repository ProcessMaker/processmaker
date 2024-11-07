<template>
  <div
    v-if="summaryScreen"
    class="tw-flex tw-w-full">
    <vue-form-renderer
      ref="screen"
      v-model="dataSummary"
      :config="summaryScreen?.config"
      :custom-css="summaryScreen?.custom_css"
      :computed="summaryScreen?.computed" />
  </div>

  <MessageDefault
    v-else-if="request?.summary.length === 0"
    :title="$t('No Data Found')"
    :message="$t('Sorry, this request doesn\'t contain any information.')" />

  <DataSummary
    v-else-if="request?.summary.length"
    class="tw-mt-3"
    :summary="dataSummary" />
</template>

<script setup>
import { computed } from "vue";
import DataSummary from "./DataSummary.vue";
import { dateFormatSummary } from "./util";
import MessageDefault from "./MessageDefault.vue";

const props = defineProps({
  request: {
    type: Object,
    required: true,
  },
});

const dataSummary = computed(() => dateFormatSummary(props.request.summary));

const summaryScreen = computed(() => props.request?.summary_screen);
</script>
