<template>
  <div class="monitor-page tw-flex tw-flex-col tw-flex-1 tw-min-h-0 tw-overflow-y-auto tw-gap-6 tw-pr-1">
    <div class="tw-flex tw-flex-wrap tw-items-center tw-justify-between tw-gap-3">
      <div class="tw-flex tw-items-center tw-gap-1 tw-bg-gray-100 tw-rounded-lg tw-p-1">
        <button
          v-for="option in executionOptions"
          :key="option.value"
          type="button"
          class="tw-rounded-lg tw-px-3 tw-py-1.5 tw-text-sm"
          :class="executionType === option.value
            ? 'tw-bg-white tw-font-semibold tw-text-zinc-900'
            : 'tw-text-zinc-700 hover:tw-bg-zinc-50'"
          @click="setExecutionType(option.value)"
        >
          {{ $t(option.label) }}
        </button>
      </div>
      <div class="tw-flex tw-items-center tw-gap-1 tw-bg-gray-100 tw-rounded-lg tw-p-1">
        <button
          v-for="option in rangeOptions"
          :key="option.value"
          type="button"
          class="tw-rounded-lg tw-px-3 tw-py-1.5 tw-text-sm"
          :class="range === option.value
            ? 'tw-bg-white tw-font-semibold tw-text-zinc-900'
            : 'tw-text-zinc-700 hover:tw-bg-zinc-50'"
          @click="setRange(option.value)"
        >
          {{ $t(option.label) }}
        </button>
      </div>
    </div>

    <div
      v-if="loading"
      class="tw-flex tw-flex-1 tw-items-center tw-justify-center tw-min-h-[200px]"
    >
      <span class="tw-text-sm tw-text-gray-500">{{ $t('Loading...') }}</span>
    </div>

    <template v-else>
      <div class="tw-grid tw-grid-cols-2 lg:tw-grid-cols-6 tw-gap-3">
        <div
          v-for="kpi in kpiCards"
          :key="kpi.label"
          class="tw-rounded-xl tw-border tw-border-zinc-200 tw-bg-white tw-px-4 tw-py-3"
        >
          <div
            class="tw-text-2xl tw-font-semibold"
            :class="kpi.toneClass"
          >
            {{ kpi.value }}
          </div>
          <div class="tw-text-xs tw-text-zinc-500 tw-mt-1">
            {{ $t(kpi.label) }}
          </div>
        </div>
      </div>

      <h3 class="tw-text-sm tw-font-semibold tw-uppercase tw-text-zinc-500 tw-m-0">
        {{ $t('Guardrails') }}
      </h3>

      <div class="monitor-grid tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-4">
        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Guardrail outcomes') }}
          </h4>
          <chart-frame v-if="hasOutcomes">
            <doughnut-chart
              :data="outcomesChart"
              :options="doughnutOptions"
            />
          </chart-frame>
          <p
            v-else
            class="tw-text-sm tw-text-zinc-500"
          >
            {{ $t(emptyGuardrails) }}
          </p>
        </section>

        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Block rate by FlowGenie') }}
          </h4>
          <chart-frame v-if="hasBlockRates">
            <horizontal-bar-chart
              :data="blockRateChart"
              :options="percentBarOptions"
            />
          </chart-frame>
          <p
            v-else
            class="tw-text-sm tw-text-zinc-500"
          >
            {{ $t(emptyBlocks) }}
          </p>
        </section>
      </div>

      <div class="monitor-grid tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-4">
        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Violations by kind') }}
          </h4>
          <chart-frame v-if="hasKinds">
            <horizontal-bar-chart
              :data="kindsChart"
              :options="countBarOptions"
            />
          </chart-frame>
          <p
            v-else
            class="tw-text-sm tw-text-zinc-500"
          >
            {{ $t('No violations in this period.') }}
          </p>
        </section>

        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Where it fired') }}
          </h4>
          <chart-frame v-if="hasPhases">
            <bar-chart
              :data="phasesChart"
              :options="stackedBarOptions"
            />
          </chart-frame>
          <p
            v-else
            class="tw-text-sm tw-text-zinc-500"
          >
            {{ $t(emptyGuardrails) }}
          </p>
        </section>
      </div>

      <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
        <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
          {{ $t('Blocked vs redacted sessions') }}
        </h4>
        <chart-frame>
          <line-chart
            :data="dailyGuardrailChart"
            :options="lineOptions"
          />
        </chart-frame>
      </section>

      <h3 class="tw-text-sm tw-font-semibold tw-uppercase tw-text-zinc-500 tw-m-0">
        {{ $t('Usage') }}
      </h3>

      <div class="monitor-grid tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-4">
        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Tokens in vs out') }}
          </h4>
          <chart-frame v-if="hasTokens">
            <doughnut-chart
              :data="tokensChart"
              :options="doughnutOptions"
            />
          </chart-frame>
          <p
            v-else
            class="tw-text-sm tw-text-zinc-500"
          >
            {{ $t(emptyTokens) }}
          </p>
        </section>

        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Tokens by FlowGenie') }}
          </h4>
          <chart-frame v-if="hasTokensByGenie">
            <horizontal-bar-chart
              :data="tokensByGenieChart"
              :options="countBarOptions"
            />
          </chart-frame>
          <p
            v-else
            class="tw-text-sm tw-text-zinc-500"
          >
            {{ $t(emptyTokens) }}
          </p>
        </section>
      </div>

      <div class="monitor-grid tw-grid tw-grid-cols-1 lg:tw-grid-cols-2 tw-gap-4">
        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Sessions per day') }}
          </h4>
          <chart-frame>
            <line-chart
              :data="dailySessionsChart"
              :options="lineOptions"
            />
          </chart-frame>
        </section>

        <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white">
          <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
            {{ $t('Session status') }}
          </h4>
          <chart-frame v-if="hasStatus">
            <doughnut-chart
              :data="statusChart"
              :options="doughnutOptions"
            />
          </chart-frame>
          <p
            v-else
            class="tw-text-sm tw-text-zinc-500"
          >
            {{ $t('No sessions in this period.') }}
          </p>
        </section>
      </div>

      <section class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-4 tw-bg-white tw-mb-4">
        <h4 class="tw-text-base tw-font-semibold tw-text-zinc-900 tw-mb-3">
          {{ $t('Top users by blocks') }}
        </h4>
        <div class="monitor-table">
          <table
            v-if="topUsers.length"
            class="tw-w-full tw-text-sm tw-text-left"
          >
          <thead>
            <tr class="tw-text-xs tw-uppercase tw-text-zinc-500">
              <th class="tw-py-2 tw-font-medium">{{ $t('User') }}</th>
              <th class="tw-py-2 tw-font-medium">{{ $t('FlowGenie') }}</th>
              <th class="tw-py-2 tw-font-medium tw-text-right">{{ $t('Sessions') }}</th>
              <th class="tw-py-2 tw-font-medium tw-text-right">{{ $t('Blocks') }}</th>
              <th class="tw-py-2 tw-font-medium tw-text-right">{{ $t('Block rate') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in topUsers"
              :key="`${row.user}-${row.genie}`"
              class="tw-border-t tw-border-zinc-100"
            >
              <td class="tw-py-2">{{ row.user }}</td>
              <td class="tw-py-2">{{ row.genie }}</td>
              <td class="tw-py-2 tw-text-right">{{ row.sessions }}</td>
              <td class="tw-py-2 tw-text-right tw-text-red-600 tw-font-medium">{{ row.blocks }}</td>
              <td class="tw-py-2 tw-text-right">{{ formatRate(row.block_rate) }}</td>
            </tr>
          </tbody>
        </table>
        <p
          v-else
          class="tw-text-sm tw-text-zinc-500"
        >
          {{ $t(emptyBlocks) }}
        </p>
        </div>
      </section>
    </template>
  </div>
</template>

<script>
import BarChart from '../charts/BarChart.vue';
import ChartFrame from '../charts/ChartFrame.vue';
import DoughnutChart from '../charts/DoughnutChart.vue';
import HorizontalBarChart from '../charts/HorizontalBarChart.vue';
import LineChart from '../charts/LineChart.vue';
import {
  CHART_COLORS,
  doughnutOptions,
  horizontalBarOptions,
  lineOptions,
  stackedBarOptions,
} from '../charts/chartTheme';

const EMPTY_MONITORING = () => ({
  kpis: {
    sessions: 0,
    block_rate: 0,
    redact_rate: 0,
    error_rate: 0,
    tokens: 0,
    avg_duration_ms: 0,
  },
  outcomes: { pass: 0, redact: 0, block: 0 },
  block_rate_by_genie: { labels: [], values: [] },
  kinds: { labels: [], values: [] },
  phases: { labels: [], block: [], redact: [] },
  daily_guardrails: { labels: [], blocked_sessions: [], redacted_sessions: [] },
  tokens: { input: 0, output: 0 },
  tokens_by_genie: { labels: [], values: [] },
  daily_sessions: { labels: [], studio: [], runtime: [] },
  status: { completed: 0, error: 0, processing: 0 },
  top_users: [],
});

export default {
  components: {
    BarChart,
    ChartFrame,
    DoughnutChart,
    HorizontalBarChart,
    LineChart,
  },
  data() {
    return {
      loading: false,
      range: '7d',
      executionType: 'all',
      monitoring: EMPTY_MONITORING(),
      emptyGuardrails: 'No guardrail evaluations in this period.',
      emptyBlocks: 'No blocked sessions in this period.',
      emptyTokens: 'No token usage in this period.',
      doughnutOptions,
      stackedBarOptions,
      lineOptions,
      percentBarOptions: horizontalBarOptions('%'),
      countBarOptions: horizontalBarOptions(),
      rangeOptions: [
        { value: '7d', label: 'Last 7 days' },
        { value: '30d', label: 'Last 30 days' },
        { value: '90d', label: 'Last 90 days' },
      ],
      executionOptions: [
        { value: 'all', label: 'All' },
        { value: 'design', label: 'Studio' },
        { value: 'execution', label: 'Runtime' },
      ],
    };
  },
  computed: {
    kpiCards() {
      const kpis = this.monitoring.kpis || {};
      return [
        { label: 'Sessions', value: this.formatCount(kpis.sessions), toneClass: 'tw-text-zinc-900' },
        { label: 'Block rate', value: this.formatRate(kpis.block_rate), toneClass: 'tw-text-red-600' },
        { label: 'Redact rate', value: this.formatRate(kpis.redact_rate), toneClass: 'tw-text-amber-600' },
        { label: 'Error rate', value: this.formatRate(kpis.error_rate), toneClass: 'tw-text-zinc-900' },
        { label: 'Tokens', value: this.formatTokens(kpis.tokens), toneClass: 'tw-text-zinc-900' },
        { label: 'Avg duration', value: this.formatDuration(kpis.avg_duration_ms), toneClass: 'tw-text-zinc-900' },
      ];
    },
    hasOutcomes() {
      const outcomes = this.monitoring.outcomes || {};
      return (outcomes.pass || 0) + (outcomes.redact || 0) + (outcomes.block || 0) > 0;
    },
    hasBlockRates() {
      return (this.monitoring.block_rate_by_genie?.values || []).some((value) => value > 0);
    },
    hasKinds() {
      return (this.monitoring.kinds?.values || []).some((value) => value > 0);
    },
    hasPhases() {
      const phases = this.monitoring.phases || {};
      const blocks = (phases.block || []).reduce((sum, value) => sum + value, 0);
      const redacts = (phases.redact || []).reduce((sum, value) => sum + value, 0);
      return blocks + redacts > 0;
    },
    hasTokens() {
      const tokens = this.monitoring.tokens || {};
      return (tokens.input || 0) + (tokens.output || 0) > 0;
    },
    hasTokensByGenie() {
      return (this.monitoring.tokens_by_genie?.values || []).some((value) => value > 0);
    },
    hasStatus() {
      const status = this.monitoring.status || {};
      return (status.completed || 0) + (status.error || 0) + (status.processing || 0) > 0;
    },
    topUsers() {
      return this.monitoring.top_users || [];
    },
    outcomesChart() {
      const outcomes = this.monitoring.outcomes || {};
      return this.doughnutData(
        [this.$t('Pass'), this.$t('Redact'), this.$t('Block')],
        [outcomes.pass || 0, outcomes.redact || 0, outcomes.block || 0],
        [CHART_COLORS.pass, CHART_COLORS.redact, CHART_COLORS.block],
      );
    },
    blockRateChart() {
      const series = this.monitoring.block_rate_by_genie || { labels: [], values: [] };
      return this.barData(series.labels, series.values, CHART_COLORS.block, this.$t('Block rate'));
    },
    kindsChart() {
      const series = this.monitoring.kinds || { labels: [], values: [] };
      return this.barData(series.labels, series.values, CHART_COLORS.redact, this.$t('Violations'));
    },
    phasesChart() {
      const phases = this.monitoring.phases || { labels: [], block: [], redact: [] };
      return {
        labels: (phases.labels || []).map((phase) => this.formatPhase(phase)),
        datasets: [
          {
            label: this.$t('Block'),
            data: phases.block || [],
            backgroundColor: CHART_COLORS.block,
          },
          {
            label: this.$t('Redact'),
            data: phases.redact || [],
            backgroundColor: CHART_COLORS.redact,
          },
        ],
      };
    },
    dailyGuardrailChart() {
      const series = this.monitoring.daily_guardrails || {};
      return {
        labels: series.labels || [],
        datasets: [
          this.lineDataset(this.$t('Blocked sessions'), series.blocked_sessions || [], CHART_COLORS.block),
          this.lineDataset(this.$t('Redacted sessions'), series.redacted_sessions || [], CHART_COLORS.redact),
        ],
      };
    },
    tokensChart() {
      const tokens = this.monitoring.tokens || {};
      return this.doughnutData(
        [this.$t('Input'), this.$t('Output')],
        [tokens.input || 0, tokens.output || 0],
        [CHART_COLORS.info, CHART_COLORS.muted],
      );
    },
    tokensByGenieChart() {
      const series = this.monitoring.tokens_by_genie || { labels: [], values: [] };
      return this.barData(series.labels, series.values, CHART_COLORS.info, this.$t('Tokens'));
    },
    dailySessionsChart() {
      const series = this.monitoring.daily_sessions || {};
      return {
        labels: series.labels || [],
        datasets: [
          this.lineDataset(this.$t('Studio'), series.studio || [], CHART_COLORS.info),
          this.lineDataset(this.$t('Runtime'), series.runtime || [], CHART_COLORS.muted),
        ],
      };
    },
    statusChart() {
      const status = this.monitoring.status || {};
      return this.doughnutData(
        [this.$t('Completed'), this.$t('Error')],
        [status.completed || 0, status.error || 0],
        [CHART_COLORS.completed, CHART_COLORS.error],
      );
    },
  },
  watch: {
    '$route.query': {
      handler() {
        this.syncQuery();
        this.fetchMonitoring();
      },
    },
  },
  mounted() {
    this.syncQuery();
    this.fetchMonitoring();
  },
  methods: {
    syncQuery() {
      this.range = this.$route.query.range || '7d';
      this.executionType = this.$route.query.execution_type || 'all';
    },
    setRange(range) {
      this.range = range;
      this.pushQuery();
    },
    setExecutionType(executionType) {
      this.executionType = executionType;
      this.pushQuery();
    },
    pushQuery() {
      const query = {
        range: this.range,
        execution_type: this.executionType,
      };
      this.$router.replace({ query }).catch((error) => {
        if (error.name !== 'NavigationDuplicated') {
          throw error;
        }
      });
    },
    async fetchMonitoring() {
      this.loading = true;
      try {
        const response = await ProcessMaker.apiClient.get('/api/1.0/package-ai/agent/logs/monitoring', {
          params: {
            range: this.range,
            execution_type: this.executionType,
          },
        });
        this.monitoring = response.data.monitoring || EMPTY_MONITORING();
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error(error);
        this.monitoring = EMPTY_MONITORING();
      } finally {
        this.loading = false;
      }
    },
    doughnutData(labels, data, colors) {
      return {
        labels,
        datasets: [{
          data,
          backgroundColor: colors,
          borderWidth: 0,
        }],
      };
    },
    barData(labels, data, color, label) {
      return {
        labels,
        datasets: [{
          label,
          data,
          backgroundColor: color,
        }],
      };
    },
    lineDataset(label, data, color) {
      return {
        label,
        data,
        borderColor: color,
        backgroundColor: color,
        fill: false,
        lineTension: 0.2,
        pointRadius: 3,
      };
    },
    formatPhase(phase) {
      const labels = {
        on_input: this.$t('On input'),
        before_tool: this.$t('Before tool'),
        after_tool: this.$t('After tool'),
        before_response: this.$t('Before response'),
        on_output: this.$t('On output'),
      };
      return labels[phase] || phase;
    },
    formatCount(value) {
      return Number(value || 0).toLocaleString();
    },
    formatRate(value) {
      return `${Number(value || 0).toFixed(1)}%`;
    },
    formatTokens(value) {
      const amount = Number(value || 0);
      if (amount >= 1000000) {
        return `${(amount / 1000000).toFixed(1)}M`;
      }
      if (amount >= 1000) {
        return `${(amount / 1000).toFixed(1)}K`;
      }
      return amount.toLocaleString();
    },
    formatDuration(ms) {
      const amount = Number(ms || 0);
      if (amount <= 0) {
        return '-';
      }
      if (amount < 1000) {
        return `${Math.round(amount)}ms`;
      }
      return `${(amount / 1000).toFixed(1)}s`;
    },
  },
};
</script>

<style scoped>
.monitor-page {
  min-width: 0;
  overflow-x: hidden;
}
.monitor-grid > * {
  min-width: 0;
  overflow: hidden;
}
.monitor-table {
  width: 100%;
  overflow-x: auto;
}
</style>
