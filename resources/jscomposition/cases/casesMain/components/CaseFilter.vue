<template>
  <div class="tw-flex tw-space-x-2">
    <div class="tw-w-full">
      <InputLeading
        v-model="model"
        placeholder="Search here"
        @change="onChange"
        @keypress="onKeypress"
      />
    </div>
  </div>
</template>
<script>
import { defineComponent, ref } from "vue";
import { InputLeading } from "../../../base/form/index";

export default defineComponent({
  components: {
    InputLeading,
  },
  emits: ["keypress", "change"],
  setup(props, { emit }) {
    const model = ref();

    const onChange = (val) => {
      emit("change", val);
    };

    const onKeypress = (val) => {
      if (val.charCode === 13) {
        emit("enter", model.value);
      }

      emit("keypress", val);
    };

    return {
      model,
      onChange,
      onKeypress,
    };
  },
});
</script>
