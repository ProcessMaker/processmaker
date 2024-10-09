<template>
  <div class="tw-flex tw-space-x-2 tw-justify-end tw-font-semibold">
    <OutlineButton
      class="!tw-py-2"
      @click="$emit('cancel')">
      <span>{{ $t("Cancel") }}</span>
    </OutlineButton>

    <OutlineButton
      class="!tw-py-2"
      @click="$emit('clear')">
      <span>{{ $t("Clear") }}</span>
    </OutlineButton>

    <OutlineButton
      class="!tw-py-2 tw-bg-gray-500 tw-text-white hover:tw-bg-gray-700"
      @click="$emit('apply')">
      <span>{{ $t("Apply") }}</span>
    </OutlineButton>
  </div>
</template>
<script setup>
import { OutlineButton } from "../../../../base/index";

const emit = defineEmits(["cancel", "clear", "apply"]);
</script>
