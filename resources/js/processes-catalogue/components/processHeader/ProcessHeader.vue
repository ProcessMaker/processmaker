<template>
  <div class="tw-flex tw-flex-row tw-w-full tw-p-4 tw-justify-between tw-items-center">
    <div class="tw-flex tw-flex-row tw-space-x-4 tw-items-center">
      <i
        class="fas fa-chevron-left tw-p-2 hover:tw-bg-gray-200 tw-rounded-lg hover:tw-cursor-pointer"
        @click="$emit('goBackCategory')" />

      <div class="tw-truncate tw-text-lg tw-font-semibold">
        {{ process.name }}
      </div>
    </div>

    <div class="tw-flex tw-items-center tw-flex-shrink-0">
      <button
        class="tw-size-8 hover:tw-bg-gray-200 tw-font-bold tw-rounded-lg tw-cursor-pointer tw-text-white"
        :class="{ 'tw-bg-blue-400': showProcessInfo,
                  'tw-bg-gray-400': !showProcessInfo }"
        @click.stop="handleInfoClick">
        <span>i</span>
      </button>
      <div class="card-bookmark mx-3">
        <i
          v-b-tooltip.hover.bottom
          :title="isBookmarked ? $t('Remove from Bookmarked List') : $t('Add to Bookmarked List')"
          class="fas fa-bookmark tw-text-lg"
          :class="{ 'tw-text-amber-400': isBookmarked,
                    'tw-text-gray-200': !isBookmarked }"
          @click="updateBookmark" />
      </div>
      <span class="ellipsis-border">
        <ellipsis-menu
          :actions="processLaunchpadActions"
          :data="process"
          :divider="false"
          :lauchpad="true"
          variant="none"
          :is-documenter-installed="$root.isDocumenterInstalled"
          :permission="$root.permission || ellipsisPermission"
          @navigate="ellipsisNavigate" />
      </span>
      <!-- <buttons-start
        :process="process"
        :start-event="singleStartEvent"
        :process-events="processEvents" /> -->
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import { updateProcessBookmark, deleteProcessBookmark } from "../api";

const props = defineProps({
  process: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["goBackCategory"]);

const isBookmarked = ref(props.process?.bookmark_id);
const showProcessInfo = ref(false);
const processLaunchpadActions = ref([]);

const handleInfoClick = () => {
  showProcessInfo.value = !showProcessInfo.value;
};

const updateBookmark = async () => {
  if (isBookmarked.value) {
    deleteProcessBookmark(props.process.id);
    isBookmarked.value = false;
  } else {
    const response = await updateProcessBookmark(props.process.id);
    isBookmarked.value = response.newId;
  }
};

</script>
