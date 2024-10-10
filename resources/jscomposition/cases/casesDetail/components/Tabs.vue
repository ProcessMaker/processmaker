<template>
  <div class="tw-flex tw-flex-col tw-pointer-events-auto">
    <nav
      class="tw-mb-px tw-flex tw-space-x-2 tw-border-b tw-border-gray-300"
      aria-label="Tabs">
      <template v-for="(tab, index) in tabs">
        <a
          v-if="tab.show"
          :key="index"
          :href="tab.href"
          class="tw-whitespace-nowrap tw-border-b-2 tw-px-1 tw-py-4 tw-text-sm tw-font-medium"
          :class="[tabSelected === tab.current ? 'tw-border-blue-500 tw-text-blue-500'
            : 'tw-border-transparent tw-text-gray-500 hover:tw-border-gray-600 hover:tw-text-gray-600'
          ]"
          :aria-current="tab.current ? 'page' : undefined"
          @click="selectTab(tab)">
          {{ tab.name }}
        </a>
      </template>
    </nav>
    <div class="tw-flex tw-grow tw-overflow-hidden">
      <slot :name="`${tabSelected}`">
        <component :is="content" />
      </slot>
    </div>
  </div>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";

export default defineComponent({
  props: {
    tabDefault: {
      type: String,
      required: true,
    },
    tabs: {
      type: Array,
      required: true,
    },
  },
  setup(props) {
    const content = ref(null);
    const tabSelected = ref(props.tabDefault);
    const selectTab = (tab) => {
      content.value = tab.content;
      tabSelected.value = tab.current;
    };

    const defaultTab = () => props.tabs.find((tab) => tab.current === tabSelected.value);

    onMounted(() => {
      selectTab(defaultTab());
    });

    return {
      content,
      selectTab,
      tabSelected,
    };
  },
});
</script>
