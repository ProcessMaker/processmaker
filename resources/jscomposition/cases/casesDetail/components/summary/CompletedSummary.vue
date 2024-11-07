<template>
  <div
    v-if="request?.summary_screen"
    class="tw-flex tw-w-full">
    <vue-form-renderer
      ref="screen"
      v-model="dataSummary"
      :config="request?.summary_screen?.config"
      :custom-css="request?.summary_screen?.custom_css"
      :computed="request?.summary_screen?.computed" />
  </div>

  <DataSummary
    v-else-if="request?.summary.length > 0"
    class="tw-mt-3"
    :summary="dataSummary" />

  <MessageDefault
    v-else
    :title="$t('No Data Found')"
    :message="$t('Sorry, this request doesn\'t contain any information.')" />
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
</script>
