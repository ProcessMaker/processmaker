<template>
  <div class="tw-flex tw-space-x-2">
    <input
      v-for="(element, index) in inputs"
      :key="index"
      :placeholder="placeholder"
      v-model="inputs[index].value"
      @change="onChange()"
      type="text"
      class="tw-p-2 tw-rounded-md tw-border-0
        tw-ring-1 tw-ring-inset tw-ring-gray-300 placeholder:tw-text-gray-400
        focus:tw-ring-2 focus:tw-ring-inset"  />
  </div>
</template>
<script>
import { defineComponent, ref } from "vue";
export default defineComponent({
  emits: ["change"],
  props:{
    placeholder: {
      type: String,
      default: ()=> ('value')
    }
  },
  setup(props, { emit }) {
    const inputs = ref([
      {
        id: "from",
        value: null,
      },
      {
        id: "to",
        value: null,
      },
    ]);

    const onChange = () => {
      emit("change", inputs.value);
    };

    return {
      inputs,
      onChange,
    };
  },
});
</script>
