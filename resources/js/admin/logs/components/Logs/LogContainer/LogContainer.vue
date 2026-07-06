<template>
  <div class="tw-flex tw-gap-3 tw-px-4 tw-h-full tw-min-h-0 tw-overflow-hidden">
    <sidebar class="tw-shrink-0" />

    <section
      class="tw-min-w-0 tw-min-h-0 tw-overflow-hidden tw-transition-all tw-duration-300"
      :class="selectedSession ? 'tw-w-1/2' : 'tw-flex-1'"
    >
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

          <RouterView
            ref="routerView"
            class="tw-flex tw-flex-col tw-flex-1 tw-min-h-0"
            @session-selected="onSessionSelected"
          />
        </div>
      </div>
    </section>

    <!-- Session Detail Panel -->
    <transition name="slide-fade">
      <section
        v-if="selectedSession"
        class="tw-w-1/2 tw-min-h-0 tw-overflow-hidden tw-min-w-[680px]"
      >
        <div class="tw-rounded-xl tw-border tw-border-zinc-200 tw-bg-white tw-h-full tw-overflow-hidden">
          <agent-session-detail
            :session="selectedSession"
            @close="onCloseDetail"
          />
        </div>
      </section>
    </transition>
  </div>
</template>

<script>
import { Sidebar } from '../Sidebar';
import { HeaderBar } from '../HeaderBar';
import { AgentSessionDetail } from '../AgentSessionDetail';

export default {
  components: {
    Sidebar,
    HeaderBar,
    AgentSessionDetail,
  },
  data() {
    return {
      search: '',
      selectedSession: null,
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
          design: 'FlowGenie Studio Logs',
          execution: 'Runtime Logs',
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
  watch: {
    // Clear selection when navigating to different log type
    '$route.path': {
      handler() {
        this.selectedSession = null;
      },
    },
  },
  methods: {
    onHandleSearch() {
      if (this.$refs.routerView) {
        this.$refs.routerView.refresh({ search: this.search });
      }
    },
    onSessionSelected(session) {
      this.selectedSession = session;
    },
    onCloseDetail() {
      this.selectedSession = null;
      // Also clear selection in the table
      if (this.$refs.routerView?.clearSelection) {
        this.$refs.routerView.clearSelection();
      }
    },
  },
};
</script>

<style scoped>
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease;
}

.slide-fade-enter,
.slide-fade-leave-to {
  transform: translateX(20px);
  opacity: 0;
}
</style>
