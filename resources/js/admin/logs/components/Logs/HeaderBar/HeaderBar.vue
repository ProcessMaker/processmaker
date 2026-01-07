<template>
  <div class="tw-flex tw-flex-row tw-gap-x-8 tw-justify-between tw-w-full sm:tw-flex-row">
    <!-- Email log type tabs - only shown for email category -->
    <div
      v-if="isEmailCategory"
      class="tw-flex tw-items-center tw-gap-2 tw-bg-gray-100 tw-rounded-lg tw-p-1"
    >
      <RouterLink
        to="/email/errors"
        class="tw-rounded-lg tw-px-3 tw-py-2 tw-text-base"
        :class="tabClasses('errors')"
      >
        Error Logs
      </RouterLink>
      <RouterLink
        to="/email/matched"
        class="tw-rounded-lg tw-px-3 tw-py-2 tw-text-base"
        :class="tabClasses('matched')"
      >
        Matched Logs
      </RouterLink>
      <RouterLink
        to="/email/total"
        class="tw-rounded-lg tw-px-3 tw-py-2 tw-text-base"
        :class="tabClasses('total')"
      >
        Total Logs
      </RouterLink>
    </div>

    <!-- Agents category tabs -->
    <div
      v-else-if="isAgentsCategory"
      class="tw-flex tw-items-center tw-gap-2 tw-bg-gray-100 tw-rounded-lg tw-p-1"
    >
      <RouterLink
        to="/agents/design"
        class="tw-rounded-lg tw-px-3 tw-py-2 tw-text-base"
        :class="tabClasses('design')"
      >
        Design Mode Logs
      </RouterLink>
      <RouterLink
        to="/agents/execution"
        class="tw-rounded-lg tw-px-3 tw-py-2 tw-text-base"
        :class="tabClasses('execution')"
      >
        Execution Logs
      </RouterLink>
    </div>

    <!-- Empty placeholder for other categories -->
    <div v-else />

    <div class="tw-flex tw-flex-1 tw-items-center tw-gap-1 tw-w-auto tw-border tw-border-zinc-200 tw-rounded-lg tw-p-1 tw-px-3">
      <div class="tw-relative tw-w-full tw-flex tw-items-center tw-gap-1">
        <i class="fas fa-search" />
        <input
          ref="searchInput"
          type="text"
          class="
            tw-h-8
            tw-w-full
            tw-pl-3
            tw-pr-3
            tw-text-sm
            tw-outline-none
            tw-ring-0
            placeholder:tw-text-zinc-400
          "
          placeholder="Search here"
          :value="value"
          @input="onInput"
          @keypress="onKeypress"
        >
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    value: { type: String, default: '' },
  },
  computed: {
    isEmailCategory() {
      return this.$route.path.startsWith('/email');
    },
    isAgentsCategory() {
      return this.$route.path.startsWith('/agents');
    },
  },
  watch: {
    '$route.path': {
      handler() {
        // reset input value in search when route changes
        this.$emit('input', '');
      },
      immediate: true,
    },
  },
  methods: {
    tabClasses(tab) {
      const currentRoute = this.$route.params.logType;

      return currentRoute === tab
        ? 'tw-bg-white tw-font-semibold tw-text-zinc-900'
        : 'tw-text-zinc-700 hover:tw-bg-zinc-50';
    },
    onInput(event) {
      this.$emit('input', event.target.value);
    },
    onKeypress(event) {
      if (event.charCode === 13) {
        this.$emit('search');
      }
    },
  },
};
</script>

