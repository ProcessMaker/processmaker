<template>
  <div
    v-if="requestDetailScreen"
    class="tw-flex tw-w-full">
    <vue-form-renderer
      ref="screen"
      v-model="dataSummary"
      :config="requestDetailScreen?.config"
      :custom-css="requestDetailScreen?.custom_css"
      :computed="requestDetailScreen?.computed" />
  </div>

  <MessageDefault
    v-else
    :title="$t('Request In Progress')"
    :message="`${$t('This Request is currently in progress.')}
    ${$t('This screen will be populated once the Request is completed.')}`" />
</template>

<script setup>
import { computed } from "vue";
import { dateFormatSummary } from "./util";
import MessageDefault from "./MessageDefault.vue";

const props = defineProps({
  request: {
    type: Object,
    required: true,
  },
});

const dataSummary = computed(() => dateFormatSummary(props.request.summary));

const requestDetailScreen = computed(() => props.request?.request_detail_screen);
</script>
