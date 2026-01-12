<template>
  <div class="tw-flex tw-gap-3 tw-px-4 tw-h-full tw-min-h-0 tw-overflow-hidden">
    <sidebar class="tw-shrink-0" />

    <section class="tw-flex-1 tw-min-w-0 tw-min-h-0 tw-overflow-hidden">
      <div class="tw-flex tw-flex-col tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white tw-h-full">
        <header-bar
          v-model="search"
          @search="onHandleSearch"
        />

        <div class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0">
          <div class="tw-flex tw-items-center tw-justify-between tw-my-8 tw-shrink-0">
            <h2 class="tw-text-2xl tw-font-semibold tw-text-zinc-900">
              {{ $t(title) }}
            </h2>
            <div v-if="showExportButton">
              <a
                :href="getExportUrl"
                target="_blank"
                class="
                  tw-inline-flex
                  tw-items-center
                  tw-gap-2
                  tw-rounded-lg
                  tw-bg-blue-500
                  tw-px-3
                  tw-py-2
                  tw-text-sm
                  tw-font-normal
                  tw-text-white
                "
              >
                <i class="fas fa-download" />
                <span>{{ $t('Export to CSV') }}</span>
              </a>
            </div>
          </div>

          <RouterView ref="routerView" class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0" />
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { Sidebar } from '../Sidebar';
import { HeaderBar } from '../HeaderBar';

export default {
  components: {
    Sidebar,
    HeaderBar,
  },
  data() {
    return {
      search: '',
    };
  },
  computed: {
    isEmailCategory() {
      return this.$route.path.startsWith('/email');
    },
    isAgentsCategory() {
      return this.$route.path.startsWith('/agents');
    },
    logType() {
      return this.$route.params.logType;
    },
    title() {
      if (this.isAgentsCategory) {
        const agentTitles = {
          design: 'Design Mode Logs',
          execution: 'Execution Logs',
        };
        return agentTitles[this.logType] ?? 'FlowGenie Agents Logs';
      }

      const titles = {
        errors: 'Error Logs',
        matched: 'Matched Logs',
        total: 'Total Logs',
      };
      return titles[this.logType] ?? '';
    },
    showExportButton() {
      // Only show export button for email category (has export endpoint)
      return this.isEmailCategory;
    },
    getExportUrl() {
      return `/admin/logs/export/csv?type=${this.logType}&search=${this.search}`;
    },
  },
  methods: {
    onHandleSearch() {
      if (this.$refs.routerView) {
        this.$refs.routerView.refresh({ search: this.search });
      }
    },
  },
};
</script>

