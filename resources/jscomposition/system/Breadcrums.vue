<template>
  <nav class="tw-py-3 tw-px-4 tw-flex tw-text-sm tw-bg-white tw-w-full tw-border-gray-200 tw-border-y">
    <ol class="tw-flex tw-items-center tw-space-x-1">
      <li
        v-for="page in model"
        :key="page.name"
      >
        <div class="tw-flex tw-items-center">
          <i
            v-if="!page.first"
            class="fas fa-slash fa-xs tw-rotate-90 tw-mr-1"
          />
          <a
            v-if="!page.current"
            :href="page.href"
            class="tw-text-sky-600"
            :aria-current="page.current ? 'page' : undefined"
          >
            <i
              :class="page.icon"
              class="tw-mr-1"
            />
            {{ page.name }}
          </a>
          <span
            v-else
            class="tw-text-black"
          >
            {{ page.name }}
          </span>
        </div>
      </li>
    </ol>
  </nav>
</template>
<script>
import { computed, defineComponent } from "vue";

export default defineComponent({
  props: {
    pages: {
      type: Array,
      default: () => [],
    },
  },
  setup(props, { emit }) {
    const model = computed(() => props.pages);
    return { model };
  },
});
</script>
