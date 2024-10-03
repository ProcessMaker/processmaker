<template>
  <div class="tw-flex tw-space-x-2">
    <div class="tw-w-full">
      <InputLeading placeholder="Search here"></InputLeading>
    </div>
  </div>
</template>
<script>
import { OutlineButton } from "../../../base/buttons/index";
import { InputLeading } from "../../../base/form/index";
import { defineComponent } from "vue";

export default defineComponent({
  components: {
    OutlineButton,
    InputLeading,
  },
  setup() {
    return {};
  },
});
</script>
