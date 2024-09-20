<template>
  <div class="tw-flex tw-space-x-2 tw-justify-end tw-font-semibold">
    <OutlineButton class="!tw-py-2">
      <span>{{ $t("Cancel") }}</span>
    </OutlineButton>

    <OutlineButton class="!tw-py-2">
      <span>{{ $t("Clear") }}</span>
    </OutlineButton>

    <OutlineButton
      class="!tw-py-2 tw-bg-gray-500 tw-text-white hover:tw-bg-gray-700">
      <span>{{ $t("Apply") }}</span>
    </OutlineButton>
  </div>
</template>
<script>
import { OutlineButton } from "../../../base/index";
import { defineComponent } from "vue";
export default defineComponent({
  components: {
    OutlineButton,
  },
  setup() {
    return {};
  },
});
</script>
