<template>
  <div
    v-if="request?.request_detail_screen"
    class="tw-flex tw-w-full">
    <vue-form-renderer
      ref="screen"
      v-model="dataSummary"
      :config="request?.request_detail_screen?.config"
      :custom-css="request?.request_detail_screen?.custom_css"
      :computed="request?.request_detail_screen?.computed" />
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
</script>
