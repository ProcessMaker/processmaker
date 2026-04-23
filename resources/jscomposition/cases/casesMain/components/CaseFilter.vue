<template>
  <div class="tw-flex tw-space-x-2">
    <div class="tw-w-full">
      <InputLeading
        v-model="model"
        placeholder="Search here"
        data-test="search-cases-list"
        @change="onChange"
        @keypress="onKeypress"
      />
    </div>
  </div>
</template>
<script>
import { defineComponent, ref, onMounted } from "vue";
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

    const handleSearch = () => {
      emit("enter", model.value);
    };

    const onKeypress = (val) => {
      if (val.charCode === 13) {
        handleSearch();
      }

      emit("keypress", val);
    };

    const extractPMQLFromURL = () => {
      const urlParams = new URLSearchParams(window.location.search);
      return urlParams.get("pmql");
    };

    const initializeFromURL = () => {
      const urlPmql = extractPMQLFromURL();
      if (urlPmql) {
        model.value = urlPmql;
        handleSearch();
      }
    };

    onMounted(() => {
      initializeFromURL();
    });

    return {
      model,
      onChange,
      onKeypress,
      handleSearch,
      extractPMQLFromURL,
    };
  },
});
</script>
