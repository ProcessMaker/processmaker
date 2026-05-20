<template>
  <div class="tw-h-full tw-flex tw-flex-col tw-bg-white">
    <!-- Header -->
    <div class="tw-border-b tw-border-gray-200 tw-px-4 tw-py-3 tw-flex tw-items-center tw-justify-between tw-bg-gray-50 tw-shrink-0">
      <div class="tw-flex tw-items-center tw-gap-3">
        <div>
          <h3 class="tw-font-semibold tw-text-gray-800 tw-text-base tw-m-0">
            {{ sessionData.flow_genie_name || sessionData.agent_name || $t('Session Details') }}
          </h3>
          <p class="tw-text-xs tw-text-gray-500 tw-font-mono tw-m-0">{{ session.session_id }}</p>
        </div>
      </div>
      <div class="tw-flex tw-items-center tw-gap-2">
        <span
          class="tw-inline-flex tw-items-center tw-px-2.5 tw-py-1 tw-rounded-full tw-text-xs tw-font-medium"
          :class="getStatusClasses(session.status)"
        >
          <i :class="getStatusIcon(session.status)" class="tw-mr-1.5" />
          {{ formatStatus(session.status) }}
        </span>
        <button
          class="tw-p-1.5 tw-text-gray-500 hover:tw-text-gray-700 hover:tw-bg-gray-200 tw-rounded-lg tw-transition-colors tw-w-8 tw-bg-gray-50"
          @click="$emit('close')"
        >
          <i class="fas fa-times" />
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="tw-flex-1 tw-flex tw-items-center tw-justify-center">
      <div class="tw-flex tw-flex-col tw-items-center tw-gap-3">
        <svg
          class="tw-animate-spin tw-h-8 tw-w-8 tw-text-blue-500"
          xmlns="http://www.w3.org/2000/svg"
          fill="none"
          viewBox="0 0 24 24"
        >
          <circle class="tw-opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="tw-opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
        <span class="tw-text-sm tw-text-gray-500">{{ $t('Loading session details...') }}</span>
      </div>
    </div>

    <!-- Content -->
    <div v-else class="tw-flex-1 tw-overflow-auto tw-flex tw-min-h-0">
      <!-- Events Timeline -->
      <div class="tw-flex-1 tw-overflow-auto tw-p-4">
        <!-- Summary Stats -->
        <div class="tw-grid tw-grid-cols-4 tw-gap-1 tw-mb-6">
          <div class="tw-bg-gray-50 tw-rounded-lg tw-p-2 tw-flex tw-flex-col tw-justify-between tw-h-16">
            <div class="tw-text-xs tw-text-gray-500 tw-text-right">{{ $t('Duration') }}</div>
            <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-text-right">
              {{ session.duration || formatDuration(sessionData.execution_time_ms) }}
            </div>
          </div>
          <div class="tw-bg-gray-50 tw-rounded-lg tw-p-2 tw-flex tw-flex-col tw-justify-between tw-h-16">
            <div class="tw-text-xs tw-text-gray-500 tw-text-right">{{ $t('LLM Calls') }}</div>
            <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-text-right">{{ llmCallsCount }}</div>
          </div>
          <div class="tw-bg-gray-50 tw-rounded-lg tw-p-2 tw-flex tw-flex-col tw-justify-between tw-h-16">
            <div class="tw-text-xs tw-text-gray-500 tw-text-right">{{ $t('Tool Calls') }}</div>
            <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-text-right">{{ toolCallsCount }}</div>
          </div>
          <div class="tw-bg-gray-50 tw-rounded-lg tw-p-2 tw-flex tw-flex-col tw-justify-between tw-h-16">
            <div class="tw-text-xs tw-text-gray-500 tw-text-right">{{ $t('Total Tokens') }}</div>
            <div class="tw-flex tw-justify-end">
              <div class="tw-relative tw-group tw-inline-block">
                <div class="tw-text-lg tw-font-semibold tw-text-gray-800 tw-cursor-help tw-border-b tw-border-dotted tw-border-gray-400">
                  {{ session.tokens_used || formatNumber(sessionData.token_usage?.total_tokens) }}
                </div>
                <!-- Popover tooltip -->
                <div
                  class="
                    tw-absolute tw-z-50 tw-top-full tw-right-0 tw-mt-2
                    tw-hidden group-hover:tw-block
                    tw-bg-gray-900 tw-text-white tw-text-xs tw-rounded-lg tw-py-2 tw-px-3
                    tw-whitespace-nowrap tw-shadow-lg
                  "
                >
                  <!-- Arrow -->
                  <div
                    class="
                      tw-absolute tw-bottom-full tw-right-2
                      tw-border-4 tw-border-transparent tw-border-b-gray-900
                    "
                  />
                  <div class="tw-flex tw-flex-col tw-gap-1">
                    <div class="tw-flex tw-justify-between tw-gap-4">
                      <span class="tw-text-gray-400">{{ $t('Input') }}:</span>
                      <span class="tw-font-medium">{{ formatNumber(sessionData.token_usage?.input_tokens) }}</span>
                    </div>
                    <div class="tw-flex tw-justify-between tw-gap-4">
                      <span class="tw-text-gray-400">{{ $t('Output') }}:</span>
                      <span class="tw-font-medium">{{ formatNumber(sessionData.token_usage?.output_tokens) }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Events Timeline -->
        <div v-if="sortedEvents.length === 0" class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-py-12 tw-text-gray-500">
          <i class="fas fa-stream tw-text-3xl tw-mb-3 tw-text-gray-300" />
          <p>{{ $t('No events recorded for this session') }}</p>
        </div>

        <div v-else class="tw-relative">
          <div
            v-for="(event, index) in sortedEvents"
            :key="event.id || index"
            class="tw-relative tw-pb-4"
          >
            <!-- Timeline connector -->
            <div
              v-if="index < sortedEvents.length - 1"
              class="tw-absolute tw-left-3 tw-top-6 tw-bottom-0 tw-w-0.5 tw-bg-gray-200"
            />

            <!-- Event content -->
            <div class="tw-flex tw-items-start tw-gap-3">
              <!-- Icon -->
              <div
                class="tw-w-6 tw-h-6 tw-rounded-full tw-flex tw-items-center tw-justify-center tw-flex-shrink-0 tw-z-10"
                :class="getEventIconClasses(event)"
              >
                <i :class="getEventIcon(event)" class="tw-text-xs" />
              </div>

              <!-- Content -->
              <div
                class="tw-flex-1 tw-bg-white tw-border-gray-200 tw-overflow-hidden tw-cursor-pointer hover:tw-border-gray-300 tw-transition-colors"
                :class="{ 'tw-border-blue-200 tw-bg-blue-50/30': expandedEvents[index] }"
                @click="toggleEvent(index)"
              >
                <!-- Event Header -->
                <div class="tw-flex tw-items-center tw-justify-between tw-px-3 pb-2">
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <span class="tw-text-xs tw-font-medium tw-text-gray-500">{{ getEventTypeLabel(event) }}</span>
                    <span class="tw-text-sm tw-font-semibold tw-text-gray-800">{{ getEventName(event) }}</span>
                  </div>
                  <div class="tw-flex tw-items-center tw-gap-3">
                    <span v-if="event.duration_ms" class="tw-text-xs tw-text-gray-500">
                      {{ formatDuration(event.duration_ms) }}
                    </span>
                    <span class="tw-text-xs tw-text-gray-400">
                      {{ formatEventTime(event.timestamp || event.created_at) }}
                    </span>
                    <i
                      class="fas tw-text-gray-400 tw-text-xs tw-transition-transform"
                      :class="expandedEvents[index] ? 'fa-chevron-up' : 'fa-chevron-down'"
                    />
                  </div>
                </div>

                <!-- Duration bar visualization (timeline) -->
                <div class="tw-h-1.5 tw-bg-gray-100 tw-relative">
                  <div
                    class="tw-h-full tw-absolute tw-transition-all tw-rounded-sm"
                    :class="getEventBarClass(event)"
                    :style="getEventBarStyle(event, index)"
                  />
                </div>

                <!-- Expanded Content -->
                <div v-if="expandedEvents[index]" class="tw-border-t tw-border-gray-100">
                  <!-- Tool call details -->
                  <div v-if="isToolEvent(event)" class="tw-p-3">
                    <div v-if="event.status" class="tw-mb-3">
                      <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Status') }}</div>
                      <span
                        class="tw-text-xs tw-px-2 tw-py-0.5 tw-rounded"
                        :class="event.status === 'completed' ? 'tw-bg-green-100 tw-text-green-700' : 'tw-bg-red-100 tw-text-red-700'"
                      >
                        {{ event.status }}
                      </span>
                    </div>
                    <div v-if="event.arguments || event.input || event.tool_arguments" class="tw-mb-3">
                      <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Arguments') }}</div>
                      <pre class="tw-bg-gray-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-40 tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words">{{ formatJson(event.arguments || event.input || event.tool_arguments) }}</pre>
                    </div>
                    <div v-if="event.output || event.result">
                      <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Output') }}</div>
                      <pre class="tw-bg-gray-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-40 tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words">{{ formatJson(event.output || event.result) }}</pre>
                    </div>
                  </div>

                  <!-- LLM call details -->
                  <div v-else-if="isLlmEvent(event)" class="tw-p-3">
                    <div v-if="event.status" class="tw-mb-3">
                      <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Status') }}</div>
                      <span
                        class="tw-text-xs tw-px-2 tw-py-0.5 tw-rounded"
                        :class="event.status === 'completed' ? 'tw-bg-green-100 tw-text-green-700' : 'tw-bg-red-100 tw-text-red-700'"
                      >
                        {{ event.status }}
                      </span>
                      <span v-if="event.input_tokens || event.output_tokens" class="tw-text-xs tw-text-gray-500 tw-ml-2">
                        ({{ event.input_tokens || 0 }} in / {{ event.output_tokens || 0 }} out tokens)
                      </span>
                    </div>
                    <div v-if="event.error" class="tw-mb-3">
                      <div class="tw-text-xs tw-font-medium tw-text-red-500 tw-mb-1">{{ $t('Error') }}</div>
                      <pre class="tw-bg-red-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-32 tw-text-red-700 tw-whitespace-pre-wrap tw-break-words">{{ event.error }}</pre>
                    </div>
                    <div v-if="event.input_preview || event.prompt || event.instructions" class="tw-mb-3">
                      <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Input') }}</div>
                      <pre class="tw-bg-gray-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-40 tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words">{{ event.input_preview || event.prompt || event.instructions }}</pre>
                    </div>
                    <div v-if="event.output_text || event.response || event.output">
                      <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Output') }}</div>
                      <pre class="tw-bg-gray-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-40 tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words">{{ event.output_text || event.response || event.output }}</pre>
                    </div>
                  </div>

                  <!-- Message output details -->
                  <div v-else-if="isMessageEvent(event)" class="tw-p-3">
                    <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Content') }}</div>
                    <pre class="tw-bg-gray-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-48 tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words">{{ event.content }}</pre>
                  </div>

                  <!-- Reasoning details -->
                  <div v-else-if="isReasoningEvent(event)" class="tw-p-3">
                    <div class="tw-text-xs tw-font-medium tw-text-gray-500 tw-mb-1">{{ $t('Reasoning') }}</div>
                    <pre class="tw-bg-gray-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-48 tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words">{{ event.content }}</pre>
                  </div>

                  <!-- Error event details -->
                  <div v-else-if="isErrorEvent(event)" class="tw-p-3">
                    <div class="tw-text-xs tw-font-medium tw-text-red-500 tw-mb-1">{{ $t('Error Message') }}</div>
                    <pre class="tw-bg-red-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-48 tw-text-red-700 tw-whitespace-pre-wrap tw-break-words">{{ event.error || event.error_message || event.message || event.data?.error || formatJson(event) }}</pre>
                  </div>

                  <!-- Generic event details -->
                  <div v-else class="tw-p-3">
                    <pre class="tw-bg-gray-50 tw-rounded tw-p-2 tw-text-xs tw-overflow-auto tw-max-h-48 tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words">{{ formatJson(event.data || event.details || event) }}</pre>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Properties Panel -->
      <div class="tw-w-56 tw-border-l tw-border-gray-200 tw-bg-gray-50 tw-overflow-auto tw-shrink-0">
        <div class="tw-p-4">
          <h4 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center">
            <i class="fas fa-info-circle tw-mr-2 tw-text-gray-400" />
            {{ $t('Properties') }}
          </h4>

          <div class="tw-space-y-3">
            <div>
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium">{{ $t('Session ID') }}</div>
              <div class="tw-text-xs tw-text-gray-600 tw-font-mono tw-break-all">{{ session.session_id }}</div>
            </div>

            <div v-if="sessionData.model || session.model">
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium">{{ $t('Model') }}</div>
              <div class="tw-text-xs tw-text-gray-600">{{ sessionData.model || session.model }}</div>
            </div>

            <div v-if="session.user_name">
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium">{{ $t('User') }}</div>
              <div class="tw-text-xs tw-text-gray-600">{{ session.user_name }}</div>
            </div>

            <div v-if="session.created_at">
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium">{{ $t('Started') }}</div>
              <div class="tw-text-xs tw-text-gray-600">{{ formatFullDateTime(session.created_at) }}</div>
            </div>

            <div v-if="session.process_name">
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium">{{ $t('Process') }}</div>
              <div class="tw-text-xs tw-text-gray-600">{{ session.process_name }}</div>
            </div>

            <div v-if="session.node_name">
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium">{{ $t('Node') }}</div>
              <div class="tw-text-xs tw-text-gray-600">{{ session.node_name }}</div>
            </div>
          </div>

          <!-- Resources Section -->
          <div v-if="hasResources" class="tw-mt-6">
            <h4 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center">
              <i class="fas fa-cube tw-mr-2 tw-text-gray-400" />
              {{ $t('Resources') }}
            </h4>

            <div v-if="sessionData.mcp_servers_used?.length" class="tw-mb-3">
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium tw-mb-1">{{ $t('MCP Servers') }}</div>
              <div class="tw-flex tw-flex-wrap tw-gap-1">
                <span
                  v-for="server in sessionData.mcp_servers_used"
                  :key="server"
                  class="tw-px-2 tw-py-0.5 tw-bg-purple-100 tw-text-purple-700 tw-rounded tw-text-xs"
                >
                  {{ server }}
                </span>
              </div>
            </div>

            <div v-if="sessionData.collections_used?.length">
              <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium tw-mb-1">{{ $t('Collections') }}</div>
              <div class="tw-flex tw-flex-wrap tw-gap-1">
                <a
                  v-for="collection in sessionData.collections_used"
                  :key="collection.uuid || collection.id"
                  :href="`/collections/${collection.id}`"
                  class="tw-px-2 tw-py-0.5 tw-bg-blue-100 tw-text-blue-700 tw-rounded tw-text-xs hover:tw-bg-blue-200 tw-transition-colors tw-no-underline"
                  @click.stop
                >
                  {{ collection.name }}
                </a>
              </div>
            </div>
          </div>

          <!-- Configuration Section -->
          <div v-if="hasConfiguration" class="tw-mt-6">
            <h4 class="tw-text-sm tw-font-semibold tw-text-gray-700 tw-mb-3 tw-flex tw-items-center">
              <i class="fas fa-cog tw-mr-2 tw-text-gray-400" />
              {{ $t('Configuration') }}
            </h4>

            <div class="tw-space-y-3">
              <!-- Model Settings -->
              <div v-if="sessionData.model_settings">
                <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium tw-mb-1">
                  {{ $t('Model Settings') }}
                </div>
                <div class="tw-space-y-1">
                  <div
                    v-for="(value, key) in sessionData.model_settings"
                    :key="key"
                    class="tw-flex tw-justify-between tw-text-xs"
                  >
                    <span class="tw-text-gray-600">{{ formatConfigKey(key) }}</span>
                    <span class="tw-text-gray-800 tw-font-medium">{{ formatConfigValue(value) }}</span>
                  </div>
                </div>
              </div>

              <!-- Max Turns -->
              <div v-if="sessionData.max_turns">
                <div class="tw-flex tw-justify-between tw-text-xs">
                  <span class="tw-text-gray-600">{{ $t('Max Turns') }}</span>
                  <span class="tw-text-gray-800 tw-font-medium">{{ sessionData.max_turns }}</span>
                </div>
              </div>

              <!-- Instructions Preview -->
              <div v-if="configInstructions">
                <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium tw-mb-1">
                  {{ $t('Instructions') }}
                </div>
                <div
                  class="tw-text-xs tw-text-gray-700 tw-bg-gray-100 tw-rounded tw-p-2 tw-max-h-24 tw-overflow-auto tw-whitespace-pre-wrap"
                >
                  {{ truncateText(configInstructions, 300) }}
                </div>
              </div>

              <!-- Request Config Details -->
              <div v-if="hasRequestConfigDetails">
                <div class="tw-text-xs tw-text-gray-500 tw-uppercase tw-font-medium tw-mb-1">
                  {{ $t('Request Config') }}
                </div>
                <div class="tw-space-y-1">
                  <div
                    v-for="(value, key) in filteredRequestConfig"
                    :key="key"
                    class="tw-flex tw-justify-between tw-text-xs"
                  >
                    <span class="tw-text-gray-600">{{ formatConfigKey(key) }}</span>
                    <span class="tw-text-gray-800 tw-font-medium">{{ formatConfigValue(value) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AgentSessionDetail',
  props: {
    session: {
      type: Object,
      required: true,
    },
  },
  data() {
    return {
      loading: false,
      sessionData: {},
      expandedEvents: {},
      startTime: null,
    };
  },
  computed: {
    sortedEvents() {
      // The events array from the backend is already in chronological order
      // Consolidate start/end events into single events for cleaner display
      const events = this.sessionData.events || [];

      // Index events by their IDs for quick lookup
      const toolEndEvents = {};
      const llmEndEvents = {};

      events.forEach((e) => {
        if (e.type === 'tool_call_end' && e.call_id) {
          toolEndEvents[e.call_id] = e;
        }
        if (e.type === 'llm_call_end' && e.response_id) {
          llmEndEvents[e.response_id] = e;
        }
      });

      // Index llm_calls array by response_id (contains input_preview and output_text)
      const llmCallsData = {};
      const llmCalls = this.sessionData.llm_calls || [];
      llmCalls.forEach((call) => {
        if (call.response_id) {
          llmCallsData[call.response_id] = call;
        }
      });

      // Process events and consolidate
      const consolidated = [];
      const processedIds = new Set();

      // Events to skip (intermediate states, will be merged or ignored)
      const skipEventTypes = [
        'tool_call_end',
        'llm_call_end',
        'llm_in_progress',
        'llm_queued',
        'llm_created',
      ];

      events.forEach((e) => {
        const type = e.type || '';

        // Skip intermediate/end events
        if (skipEventTypes.includes(type)) {
          return;
        }

        // Consolidate tool calls
        if (type === 'tool_call_start' && e.call_id) {
          if (processedIds.has(`tool_${e.call_id}`)) return;
          processedIds.add(`tool_${e.call_id}`);

          const endEvent = toolEndEvents[e.call_id];
          consolidated.push({
            ...e,
            _type: 'tool_call',
            type: 'tool_call',
            // Merge end event data
            output: endEvent?.output,
            duration_ms: endEvent?.duration_ms,
            status: endEvent?.status || 'completed',
          });
          return;
        }

        // Consolidate LLM calls
        if (type === 'llm_call_start' && e.response_id) {
          if (processedIds.has(`llm_${e.response_id}`)) return;
          processedIds.add(`llm_${e.response_id}`);

          const endEvent = llmEndEvents[e.response_id];
          const llmCallData = llmCallsData[e.response_id] || {};

          consolidated.push({
            ...e,
            _type: 'llm_call',
            type: 'llm_call',
            // Merge end event data
            input_tokens: endEvent?.input_tokens || llmCallData.input_tokens,
            output_tokens: endEvent?.output_tokens || llmCallData.output_tokens,
            duration_ms: endEvent?.duration_ms || llmCallData.duration_ms,
            status: endEvent?.status || llmCallData.status || 'completed',
            error: endEvent?.error || llmCallData.error,
            // Merge llm_calls data (has input_preview and output_text)
            input_preview: llmCallData.input_preview,
            output_text: llmCallData.output_text,
            model: e.model || llmCallData.model,
          });
          return;
        }

        // Keep other events as-is
        consolidated.push({
          ...e,
          _type: type || 'event',
        });
      });

      // Add session-level error as an event if it exists and no error event was found
      const hasErrorEvent = consolidated.some(
        (e) => e._type === 'agent_error' || e._type === 'error' || e.error,
      );
      if (this.sessionData.error_message && !hasErrorEvent) {
        consolidated.push({
          _type: 'agent_error',
          type: 'agent_error',
          timestamp: this.sessionData.completed_at || this.sessionData.updated_at,
          error_message: this.sessionData.error_message,
        });
      }

      return consolidated;
    },
    llmCallsCount() {
      if (typeof this.session.llm_calls === 'number') {
        return this.session.llm_calls;
      }
      return this.sessionData.llm_calls?.length || 0;
    },
    toolCallsCount() {
      if (typeof this.session.tools === 'number') {
        return this.session.tools;
      }
      return this.sessionData.tools_used?.length || 0;
    },
    totalDuration() {
      return this.sessionData.execution_time_ms || this.calculatedDuration || 0;
    },
    calculatedDuration() {
      // If no execution_time_ms, calculate from events
      if (this.sortedEvents.length === 0) return 0;
      const firstEvent = this.sortedEvents[0];
      const lastEvent = this.sortedEvents[this.sortedEvents.length - 1];
      const startTime = new Date(firstEvent.timestamp || firstEvent.created_at || 0).getTime();
      const lastEventTime = new Date(lastEvent.timestamp || lastEvent.created_at || 0).getTime();
      const lastEventDuration = lastEvent.duration_ms || 0;
      return Math.max(lastEventTime - startTime + lastEventDuration, 0);
    },
    totalEventsDuration() {
      // Sum of all event durations
      return this.sortedEvents.reduce((sum, event) => sum + (event.duration_ms || 0), 0) || 1;
    },
    endTime() {
      if (!this.startTime || !this.totalDuration) return null;
      return this.startTime + this.totalDuration;
    },
    hasResources() {
      return this.sessionData.mcp_servers_used?.length || this.sessionData.collections_used?.length;
    },
    hasConfiguration() {
      return this.sessionData.model_settings
        || this.sessionData.max_turns
        || this.configInstructions
        || this.hasRequestConfigDetails;
    },
    configInstructions() {
      const config = this.sessionData.request_config || {};
      return config.instructions || config.prompt || null;
    },
    hasRequestConfigDetails() {
      return Object.keys(this.filteredRequestConfig).length > 0;
    },
    filteredRequestConfig() {
      const config = this.sessionData.request_config || {};
      // Keys to exclude (already shown elsewhere or too verbose)
      const excludeKeys = [
        'instructions',
        'prompt',
        'history',
        'mcp_servers',
        'collections',
        'model_settings',
        'aiState',
        'callback',
      ];
      const result = {};
      Object.keys(config).forEach((key) => {
        if (!excludeKeys.includes(key)) {
          const value = config[key];
          // Only include simple values (not objects/arrays)
          if (value !== null && value !== undefined && typeof value !== 'object') {
            result[key] = value;
          }
        }
      });
      return result;
    },
  },
  watch: {
    session: {
      handler(newSession) {
        if (newSession?.session_id) {
          this.fetchSessionDetails();
        }
      },
      immediate: true,
    },
  },
  methods: {
    async fetchSessionDetails() {
      if (!this.session?.session_id) return;

      this.loading = true;
      this.expandedEvents = {};

      try {
        const response = await ProcessMaker.apiClient.get(
          `/api/1.0/package-ai/agent/logs/session/${this.session.session_id}`,
        );

        if (response.data.success) {
          this.sessionData = response.data.session || {};
          this.calculateStartTime();
        }
      } catch (error) {
        // eslint-disable-next-line no-console
        console.error('Error fetching session details:', error);
        this.sessionData = {};
      } finally {
        this.loading = false;
      }
    },

    calculateStartTime() {
      if (this.sortedEvents.length > 0) {
        const firstEvent = this.sortedEvents[0];
        this.startTime = new Date(firstEvent.timestamp || firstEvent.created_at || 0).getTime();
      }
    },

    toggleEvent(index) {
      this.$set(this.expandedEvents, index, !this.expandedEvents[index]);
    },

    isToolEvent(event) {
      const type = event._type || event.type;
      return type === 'tool_call' || event.tool_name || event.tool_arguments;
    },

    isLlmEvent(event) {
      const type = event._type || event.type;
      return type === 'llm_call';
    },

    isMessageEvent(event) {
      const type = event._type || event.type;
      return type === 'message_output';
    },

    isReasoningEvent(event) {
      const type = event._type || event.type;
      return type === 'reasoning';
    },

    isErrorEvent(event) {
      const type = event._type || event.type;
      return type === 'agent_error' || type === 'error'
        || event.error || event.error_message;
    },

    getEventTypeLabel(event) {
      if (this.isErrorEvent(event)) return this.$t('Error');
      if (this.isToolEvent(event)) return this.$t('Tool');
      if (this.isLlmEvent(event)) return this.$t('LLM');
      if (this.isMessageEvent(event)) return this.$t('Message');
      if (this.isReasoningEvent(event)) return this.$t('Reasoning');

      const type = event._type || event.type;
      const labels = {
        agent_processing_started: this.$t('Agent'),
        agent_completed: this.$t('Agent'),
        connection_established: this.$t('Connection'),
      };
      return labels[type] || this.$t('Event');
    },

    getEventName(event) {
      if (this.isErrorEvent(event)) {
        return this.$t('Execution Error');
      }
      if (this.isToolEvent(event)) {
        return event.tool_name || event.name || this.$t('Tool Call');
      }
      if (this.isLlmEvent(event)) {
        return event.model || this.$t('Model Request');
      }
      if (this.isMessageEvent(event)) {
        return this.$t('Response');
      }
      if (this.isReasoningEvent(event)) {
        return this.$t('Thinking');
      }

      const type = event._type || event.type;
      const names = {
        agent_processing_started: this.$t('Processing Started'),
        agent_completed: this.$t('Completed'),
        connection_established: this.$t('Connected'),
      };
      return names[type] || type || this.$t('Event');
    },

    getEventIcon(event) {
      if (this.isErrorEvent(event)) return 'fas fa-exclamation-triangle';
      if (this.isToolEvent(event)) return 'fas fa-wrench';
      if (this.isLlmEvent(event)) return 'fas fa-brain';
      if (this.isMessageEvent(event)) return 'fas fa-comment';
      if (this.isReasoningEvent(event)) return 'fas fa-lightbulb';

      const type = event._type || event.type;
      const icons = {
        agent_processing_started: 'fas fa-play',
        agent_completed: 'fas fa-check',
        connection_established: 'fas fa-plug',
      };
      return icons[type] || 'fas fa-circle';
    },

    getEventIconClasses(event) {
      if (this.isErrorEvent(event)) return 'tw-bg-red-100 tw-text-red-600';
      if (this.isToolEvent(event)) return 'tw-bg-indigo-100 tw-text-indigo-600';
      if (this.isLlmEvent(event)) return 'tw-bg-purple-100 tw-text-purple-600';
      if (this.isMessageEvent(event)) return 'tw-bg-green-100 tw-text-green-600';
      if (this.isReasoningEvent(event)) return 'tw-bg-yellow-100 tw-text-yellow-600';

      const type = event._type || event.type;
      const classes = {
        agent_processing_started: 'tw-bg-blue-100 tw-text-blue-600',
        agent_completed: 'tw-bg-green-100 tw-text-green-600',
        connection_established: 'tw-bg-gray-100 tw-text-gray-600',
      };
      return classes[type] || 'tw-bg-gray-100 tw-text-gray-600';
    },

    getEventBarClass(event) {
      if (this.isErrorEvent(event)) return 'tw-bg-red-500';
      if (this.isToolEvent(event)) return 'tw-bg-indigo-400';
      if (this.isLlmEvent(event)) return 'tw-bg-purple-400';
      if (this.isMessageEvent(event)) return 'tw-bg-green-400';
      if (this.isReasoningEvent(event)) return 'tw-bg-yellow-400';
      return 'tw-bg-blue-400';
    },

    getEventBarStyle(event, index) {
      const eventIndex = index !== undefined ? index : 0;
      const durationMs = event.duration_ms || 0;

      // Calculate total duration from all event durations if not available from session
      const effectiveTotalDuration = this.totalDuration || this.totalEventsDuration;

      // If we have timestamps and total duration, use real timeline positioning
      if (this.startTime && effectiveTotalDuration > 0) {
        const eventTime = new Date(event.timestamp || event.created_at || 0).getTime();

        // Check if we have valid timestamps (not epoch 0)
        if (eventTime > 1000000000000) { // Valid timestamp (after year 2001)
          const offsetMs = Math.max(eventTime - this.startTime, 0);
          const offsetPercent = (offsetMs / effectiveTotalDuration) * 100;

          let widthPercent;
          if (durationMs > 0) {
            widthPercent = (durationMs / effectiveTotalDuration) * 100;
          } else {
            widthPercent = 3; // Minimum for events without duration
          }

          return {
            left: `${Math.min(offsetPercent, 99)}%`,
            width: `${Math.max(widthPercent, 1)}%`,
          };
        }
      }

      // Fallback: Sequential positioning based on accumulated durations
      // Calculate position based on sum of previous event durations
      let accumulatedOffset = 0;
      for (let i = 0; i < eventIndex; i++) {
        accumulatedOffset += this.sortedEvents[i]?.duration_ms || 0;
      }

      const totalDurationForCalc = this.totalEventsDuration || 1;
      const offsetPercent = (accumulatedOffset / totalDurationForCalc) * 100;

      let widthPercent;
      if (durationMs > 0) {
        widthPercent = (durationMs / totalDurationForCalc) * 100;
      } else {
        // For events without duration, give them a small visible width
        widthPercent = 2;
      }

      return {
        left: `${Math.min(offsetPercent, 99)}%`,
        width: `${Math.max(widthPercent, 1)}%`,
      };
    },

    getDurationBarWidth(durationMs) {
      if (!this.totalDuration || !durationMs) return 0;
      return Math.min((durationMs / this.totalDuration) * 100, 100);
    },

    getStatusClasses(status) {
      const classes = {
        completed: 'tw-bg-green-100 tw-text-green-800',
        error: 'tw-bg-red-100 tw-text-red-800',
        processing: 'tw-bg-yellow-100 tw-text-yellow-800',
      };
      return classes[status] || 'tw-bg-gray-100 tw-text-gray-800';
    },

    getStatusIcon(status) {
      const icons = {
        completed: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        processing: 'fas fa-spinner fa-spin',
      };
      return icons[status] || 'fas fa-circle';
    },

    formatStatus(status) {
      const labels = {
        completed: this.$t('Completed'),
        error: this.$t('Error'),
        processing: this.$t('Processing'),
      };
      return labels[status] || status;
    },

    formatDuration(ms) {
      if (!ms) return '-';
      if (ms < 1000) return `${ms}ms`;
      const seconds = ms / 1000;
      if (seconds < 60) return `${seconds.toFixed(1)}s`;
      const minutes = Math.floor(seconds / 60);
      const remainingSeconds = Math.round(seconds % 60);
      return `${minutes}m ${remainingSeconds}s`;
    },

    formatEventTime(timestamp) {
      if (!timestamp) return '';
      const date = new Date(timestamp);
      return date.toLocaleTimeString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
      });
    },

    formatFullDateTime(timestamp) {
      if (!timestamp) return '-';
      const date = new Date(timestamp);
      return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      });
    },

    formatNumber(num) {
      if (!num) return '0';
      return num.toLocaleString();
    },

    formatJson(data) {
      if (!data) return '';
      if (typeof data === 'string') {
        try {
          return JSON.stringify(JSON.parse(data), null, 2);
        } catch {
          return data;
        }
      }
      try {
        return JSON.stringify(data, null, 2);
      } catch {
        return String(data);
      }
    },

    formatConfigKey(key) {
      // Convert snake_case or camelCase to Title Case
      return key
        .replace(/_/g, ' ')
        .replace(/([A-Z])/g, ' $1')
        .replace(/^./, (str) => str.toUpperCase())
        .trim();
    },

    formatConfigValue(value) {
      if (value === null || value === undefined) return '-';
      if (typeof value === 'boolean') return value ? this.$t('Yes') : this.$t('No');
      if (typeof value === 'number') return value.toLocaleString();
      if (Array.isArray(value)) return value.length > 0 ? value.join(', ') : '-';
      if (typeof value === 'object') return JSON.stringify(value);
      return String(value);
    },

    truncateText(text, maxLength) {
      if (!text) return '';
      if (text.length <= maxLength) return text;
      return `${text.substring(0, maxLength)}...`;
    },
  },
};
</script>

<style scoped>
pre {
  margin: 0;
  font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
}
</style>

