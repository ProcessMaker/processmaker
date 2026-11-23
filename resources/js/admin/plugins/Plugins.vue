<template>
  <div class="data-table px-3 page-content">
    <div class="d-flex flex-column flex-md-row mb-2" v-show="!shouldShowLoader">
      <div class="flex-grow-1 mr-md-2 mb-3 mb-md-0">
        <b-input-group class="w-100">
          <b-form-input
            id="search-box"
            v-model="filter"
            :placeholder="$t('Search')"
            :aria-label="$t('Search')"
          />
          <b-input-group-append>
            <b-button variant="primary" type="button" :aria-label="$t('Search')">
              <i class="fas fa-search" />
            </b-button>
          </b-input-group-append>
        </b-input-group>
      </div>
      <div class="d-flex ml-md-2">
        <b-button variant="secondary" type="button" @click="showCreateModal" :aria-label="$t('Create Plugin')">
          <i class="fas fa-plus" />
          {{ $t("Plugin") }}
        </b-button>
      </div>
    </div>

    <data-loading
      :for="/\/plugins(\?|$)/"
      :data-loading-id="dataLoadingId"
      v-show="shouldShowLoader"
      :empty="$t('No Data Available')"
      :empty-desc="$t('No Data Available')"
      empty-icon="noData"
    />
    <div v-show="!shouldShowLoader" class="card card-body table-card">
      <vuetable
        :load-on-start="false"
        :data-manager="dataManager"
        :sort-order="sortOrder"
        :css="css"
        :api-mode="false"
        track-by="name"
        @vuetable:pagination-data="onPaginationData"
        :fields="fields"
        :data="data"
        data-path="data"
        :no-data-template="$t('No Data Available')"
        pagination-path="meta"
      >
        <template slot="name" slot-scope="props">
          <span :data-cy="'plugin-name-' + props.rowData.name">{{ props.rowData.name }}</span>
        </template>

        <template slot="enabled" slot-scope="props">
          {{ formatEnabled(props.rowData.enabled) }}
        </template>

        <template slot="actions" slot-scope="props">
          <div class="actions">
            <div class="popout">
              <b-btn
                variant="link"
                @click="doReinstall(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Update')"
                :class="props.rowData.source !== 'git' ? 'disabled' : ''"
                :disabled="props.rowData.source !== 'git'"
              >
                <i class="fas fa-sync fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="edit(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Edit')"
                :class="props.rowData.enabled !== 'Enabled' ? 'disabled' : ''"
              >
                <i class="fas fa-pen-square fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="doDelete(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Delete')"
              >
                <i class="fas fa-trash-alt fa-lg fa-fw"></i>
              </b-btn>
              <b-btn
                variant="link"
                @click="doToggle(props.rowData)"
                v-b-tooltip.hover
                :title="$t('Toggle')"
              >
                <i class="fas fa-lg fa-fw" :class="{ 'fa-toggle-on': props.rowData.enabled === 'Enabled', 'fa-toggle-off': props.rowData.enabled === 'Disabled' }"></i>
              </b-btn>
            </div>
          </div>
        </template>
      </vuetable>
      <pagination
        :single="$t('Plugin')"
        :plural="$t('Plugins')"
        :per-page-select-enabled="true"
        @changePerPage="changePerPage"
        @vuetable-pagination:change-page="onPageChange"
        ref="pagination"
      />
    </div>

    <b-modal
      ref="createPlugin"
      id="createPlugin"
      size="xl"
      content-class="create-plugin-modal"
      header-class="create-plugin-modal-header"
      footer-class="create-plugin-modal-footer"
      centered
      no-close-on-backdrop
      @shown="onCreatePluginShown"
      @hidden="onCreatePluginHidden"
    >
      <template #modal-header="{ close }">
        <div class="tw-flex tw-items-center tw-justify-between tw-w-full">
          <div class="tw-flex tw-items-center tw-gap-2">
            <div class="tw-rounded-lg tw-border tw-border-gray-300 tw-p-2 tw-flex tw-items-center tw-justify-center">
              <i class="fas fa-fw fa-plug nav-icon tw-text-gray-700" />
            </div>
            <span class="tw-text-base tw-font-medium tw-text-gray-900 tw-mb-0">{{ title }}</span>
          </div>
          <button type="button" class="close" :aria-label="$t('Close')" @click="close()">
            ×
          </button>
        </div>
      </template>

      <div class="tw-flex tw-flex-col md:tw-flex-row tw-gap-0 tw-px-1">
        <div class="create-plugin-modal__form md:tw-pr-5 tw-min-w-0">
          <required />
          <div class="tw-mb-3">
            <label for="plugin-url" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
              {{ $t("Repository URL") }} <span class="text-danger">*</span>
            </label>
            <div class="tw-flex tw-items-stretch tw-gap-2">
              <input
                id="plugin-url"
                v-model="plugin.url"
                type="text"
                name="url"
                required
                aria-required="true"
                class="tw-w-full tw-border tw-border-gray-300 tw-rounded-lg tw-px-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-transparent"
                :class="getError('url') ? 'tw-border-red-500' : ''"
                :placeholder="$t('https://github.com/org/plugin.git')"
              />
              <button
                type="button"
                class="tw-shrink-0 tw-inline-flex tw-items-center tw-justify-center tw-rounded-lg tw-border tw-border-gray-300 tw-bg-white tw-px-3 tw-text-gray-500 hover:tw-bg-gray-50 tw-transition-colors"
                :aria-pressed="showGitCredentials ? 'true' : 'false'"
                :aria-label="$t('Credentials')"
                @click="showGitCredentials = !showGitCredentials"
              >
                <span v-b-tooltip.hover.bottom :title="$t('Credentials')" class="tw-inline-flex">
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="1em"
                    height="1em"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <g
                      fill="none"
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                    >
                      <path
                        d="M16.5 10V6.5C16.5 4.01472 14.4853 2 12 2C9.51472 2 7.5 4.01472 7.5 6.5M4 13V19C4 20.6569 5.34315 22 7 22H17C18.6569 22 20 20.6569 20 19V13C20 11.3431 18.6569 10 17 10H7C5.34315 10 4 11.3431 4 13Z"
                      />
                      <path
                        fill="currentColor"
                        d="M13.5 16C13.5 16.8284 12.8284 17.5 12 17.5C11.1716 17.5 10.5 16.8284 10.5 16C10.5 15.1716 11.1716 14.5 12 14.5C12.8284 14.5 13.5 15.1716 13.5 16Z"
                        stroke="none"
                      />
                    </g>
                  </svg>
                </span>
              </button>
            </div>
            <small v-if="getError('url')" class="tw-text-red-500 tw-text-sm tw-mt-1 tw-block">
              {{ getError("url") }}
            </small>
          </div>

          <b-collapse v-model="showGitCredentials" class="tw-mb-3">
            <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-3 tw-rounded-xl tw-border tw-border-gray-200 tw-bg-gray-50 tw-p-3">
              <div>
                <label for="plugin-git-username" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                  {{ $t("Username") }}
                </label>
                <div class="create-plugin-input-icon">
                  <span class="create-plugin-input-icon__glyph" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                      <path
                        fill="currentColor"
                        d="M9.175 10.825Q8 9.65 8 8t1.175-2.825T12 4t2.825 1.175T16 8t-1.175 2.825T12 12t-2.825-1.175M4 18v-.8q0-.85.438-1.562T5.6 14.55q1.55-.775 3.15-1.162T12 13t3.25.388t3.15 1.162q.725.375 1.163 1.088T20 17.2v.8q0 .825-.587 1.413T18 20H6q-.825 0-1.412-.587T4 18m2 0h12v-.8q0-.275-.137-.5t-.363-.35q-1.35-.675-2.725-1.012T12 15t-2.775.338T6.5 16.35q-.225.125-.363.35T6 17.2zm7.413-8.587Q14 8.825 14 8t-.587-1.412T12 6t-1.412.588T10 8t.588 1.413T12 10t1.413-.587M12 18"
                      />
                    </svg>
                  </span>
                  <input
                    id="plugin-git-username"
                    v-model="plugin.gitUsername"
                    type="text"
                    name="git_username"
                    autocomplete="username"
                    class="tw-w-full tw-border tw-border-gray-300 tw-rounded-lg tw-pl-9 tw-pr-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-transparent"
                  />
                </div>
              </div>
              <div>
                <label for="plugin-git-token" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                  {{ $t("Token") }}
                </label>
                <div class="create-plugin-input-icon">
                  <span class="create-plugin-input-icon__glyph" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                      <path
                        fill="currentColor"
                        d="M2 19v-2h20v2zm1.15-6.05l-1.3-.75l.85-1.5H1V9.2h1.7l-.85-1.45L3.15 7L4 8.45L4.85 7l1.3.75L5.3 9.2H7v1.5H5.3l.85 1.5l-1.3.75l-.85-1.5zm8 0l-1.3-.75l.85-1.5H9V9.2h1.7l-.85-1.45l1.3-.75l.85 1.45l.85-1.45l1.3.75l-.85 1.45H15v1.5h-1.7l.85 1.5l-1.3.75l-.85-1.5zm8 0l-1.3-.75l.85-1.5H17V9.2h1.7l-.85-1.45l1.3-.75l.85 1.45l.85-1.45l1.3.75l-.85 1.45H23v1.5h-1.7l.85 1.5l-1.3.75l-.85-1.5z"
                      />
                    </svg>
                  </span>
                  <input
                    id="plugin-git-token"
                    v-model="plugin.gitToken"
                    type="password"
                    name="git_token"
                    autocomplete="off"
                    :aria-label="$t('Token')"
                    class="tw-w-full tw-border tw-border-gray-300 tw-rounded-lg tw-pl-9 tw-pr-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-transparent"
                  />
                </div>
              </div>
            </div>
            <small class="tw-block tw-text-gray-500 tw-mt-2">
              {{
                $t(
                  "The token is only used by ProcessMaker to clone private repositories. It is never included in the copied prompt."
                )
              }}
            </small>
          </b-collapse>

          <div class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2 tw-gap-3 tw-mb-3">
            <div>
              <label for="plugin-branch" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                {{ $t("Branch") }}
              </label>
              <div class="create-plugin-input-icon">
                <span class="create-plugin-input-icon__glyph" aria-hidden="true">
                  <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 16 16">
                    <path
                      fill="currentColor"
                      d="M5 10.038a3.5 3.5 0 0 1 2.5-1.05h2a2.5 2.5 0 0 0 2.462-2.061a2 2 0 1 1 1.008.017A3.5 3.5 0 0 1 9.5 9.987h-2a2.5 2.5 0 0 0-2.466 2.085A2 2 0 1 1 4 12.063V3.937a2 2 0 1 1 1 0zM4.5 3a1 1 0 1 0 0-2a1 1 0 0 0 0 2m0 12a1 1 0 1 0 0-2a1 1 0 0 0 0 2m8-9a1 1 0 1 0 0-2a1 1 0 0 0 0 2"
                    />
                  </svg>
                </span>
                <input
                  id="plugin-branch"
                  v-model="plugin.branch"
                  type="text"
                  name="branch"
                  class="tw-w-full tw-border tw-border-gray-300 tw-rounded-lg tw-pl-9 tw-pr-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-transparent"
                  :class="getError('branch') ? 'tw-border-red-500' : ''"
                />
              </div>
              <small v-if="getError('branch')" class="tw-text-red-500 tw-text-sm tw-mt-1 tw-block">
                {{ getError("branch") }}
              </small>
            </div>
            <div>
              <label for="plugin-tag" class="tw-block tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-2">
                {{ $t("Tag") }}
              </label>
              <div class="create-plugin-input-icon">
                <span class="create-plugin-input-icon__glyph" aria-hidden="true">
                  <svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24">
                    <g
                      fill="none"
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                    >
                      <path d="M6.5 7.5a1 1 0 1 0 2 0a1 1 0 1 0-2 0" />
                      <path
                        d="M3 6v5.172a2 2 0 0 0 .586 1.414l7.71 7.71a2.41 2.41 0 0 0 3.408 0l5.592-5.592a2.41 2.41 0 0 0 0-3.408l-7.71-7.71A2 2 0 0 0 11.172 3H6a3 3 0 0 0-3 3"
                      />
                    </g>
                  </svg>
                </span>
                <input
                  id="plugin-tag"
                  v-model="plugin.tag"
                  type="text"
                  name="tag"
                  class="tw-w-full tw-border tw-border-gray-300 tw-rounded-lg tw-pl-9 tw-pr-3 tw-py-2 tw-text-sm focus:tw-outline-none focus:tw-ring-2 focus:tw-ring-blue-500 focus:tw-border-transparent"
                  :class="getError('tag') ? 'tw-border-red-500' : ''"
                />
              </div>
              <small v-if="getError('tag')" class="tw-text-red-500 tw-text-sm tw-mt-1 tw-block">
                {{ getError("tag") }}
              </small>
            </div>
          </div>

          <p class="tw-text-xs tw-text-gray-500 tw-mb-3 tw-leading-relaxed">
            <i class="fas fa-info-circle tw-mr-1" aria-hidden="true" />
            {{
              $t(
                "New plugin? Use an empty repository (no README), copy the prompt and let your agent push the skeleton first, then save."
              )
            }}
          </p>

          <plugin-output
            :user-id="userId"
            :waiting="isRunning"
            @done="onPluginJobDone"
            @error="onPluginJobError"
            @running="onPluginJobRunning"
          />
        </div>

        <div
          class="tw-hidden md:tw-block tw-w-px tw-bg-gray-200 tw-self-stretch tw-mx-1"
          aria-hidden="true"
        />

        <div class="create-plugin-modal__mcp md:tw-pl-5 tw-pt-6 md:tw-pt-0 tw-min-w-0 tw-flex tw-flex-col">
          <div class="tw-flex tw-flex-col tw-h-full tw-justify-center tw-py-2 tw-items-stretch">
            <p class="tw-text-xs tw-font-semibold tw-tracking-wide tw-uppercase tw-text-blue-600 tw-mb-2">
              {{ $t("MCP setup") }}
            </p>
            <h5 class="tw-text-lg tw-font-semibold tw-text-gray-900 tw-mb-2">
              {{ $t("Set up ProcessMaker MCP in your coding agent") }}
            </h5>
            <p class="tw-text-sm tw-text-gray-600 tw-mb-4 tw-leading-relaxed">
              {{
                $t(
                  "Copy a ready-to-paste setup prompt for Claude, Cursor, or any coding agent. It installs the prerequisites, creates your plugin from the skeleton, pushes it to the repository above, and connects this instance over MCP."
                )
              }}
            </p>

            <div class="create-plugin-mcp-stage" aria-hidden="true">
              <div
                class="create-plugin-mcp-flow"
                :class="{
                  'is-animating': mcpFlowAnimating,
                  'is-exiting': mcpStage === 'icons-out',
                  'is-hidden': mcpStage === 'tips',
                }"
              >
                <div class="create-plugin-mcp-flow__clients">
                  <div
                    class="create-plugin-mcp-flow__badge"
                    style="--i: 0"
                    title="Claude"
                  >
                    <img
                      src="/img/admin/plugins/favicon-claude.png"
                      alt="Claude"
                      width="22"
                      height="22"
                      class="create-plugin-mcp-flow__favicon"
                    />
                  </div>
                  <div
                    class="create-plugin-mcp-flow__badge"
                    style="--i: 1"
                    title="Cursor"
                  >
                    <img
                      src="/img/admin/plugins/favicon-cursor.png"
                      alt="Cursor"
                      width="22"
                      height="22"
                      class="create-plugin-mcp-flow__favicon"
                    />
                  </div>
                  <div
                    class="create-plugin-mcp-flow__badge"
                    style="--i: 2"
                    title="Codex"
                  >
                    <img
                      src="/img/admin/plugins/favicon-codex.png"
                      alt="Codex"
                      width="22"
                      height="22"
                      class="create-plugin-mcp-flow__favicon"
                    />
                  </div>
                </div>

                <div class="create-plugin-mcp-flow__arrow" style="--i: 3">
                  <i class="fas fa-arrow-right" />
                </div>

                <div
                  class="create-plugin-mcp-flow__badge create-plugin-mcp-flow__badge--mcp"
                  style="--i: 4"
                  title="MCP"
                >
                  <svg
                    class="create-plugin-mcp-icon"
                    width="22"
                    height="24"
                    viewBox="0 0 22 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                  >
                    <path
                      fill-rule="evenodd"
                      clip-rule="evenodd"
                      d="M14.6882 2.40206C14.205 1.93182 13.5574 1.66871 12.8832 1.66871C12.209 1.66871 11.5614 1.93182 11.0782 2.40206L1.45222 11.8421C1.29113 11.9986 1.07535 12.0862 0.850715 12.0862C0.62608 12.0862 0.410297 11.9986 0.249215 11.8421C0.170352 11.7654 0.107665 11.6736 0.0648595 11.5723C0.0220544 11.471 0 11.3621 0 11.2521C0 11.1421 0.0220544 11.0332 0.0648595 10.9318C0.107665 10.8305 0.170352 10.7388 0.249215 10.6621L9.87522 1.22206C10.6804 0.438454 11.7596 0 12.8832 0C14.0068 0 15.086 0.438454 15.8912 1.22206C16.3571 1.67516 16.7091 2.23216 16.9184 2.84745C17.1277 3.46274 17.1883 4.11885 17.0952 4.76206C17.747 4.66936 18.4113 4.72755 19.037 4.93213C19.6627 5.13672 20.2331 5.48224 20.7042 5.94206L20.7542 5.99206C21.1485 6.37554 21.462 6.83418 21.676 7.34087C21.89 7.84756 22.0003 8.39202 22.0003 8.94206C22.0003 9.4921 21.89 10.0366 21.676 10.5432C21.462 11.0499 21.1485 11.5086 20.7542 11.8921L12.0482 20.4291C12.0219 20.4546 12.001 20.4851 11.9868 20.5189C11.9725 20.5527 11.9652 20.5889 11.9652 20.6256C11.9652 20.6622 11.9725 20.6985 11.9868 20.7322C12.001 20.766 12.0219 20.7965 12.0482 20.8221L13.8362 22.5761C13.9151 22.6528 13.9778 22.7445 14.0206 22.8458C14.0634 22.9472 14.0854 23.0561 14.0854 23.1661C14.0854 23.2761 14.0634 23.385 14.0206 23.4863C13.9778 23.5876 13.9151 23.6794 13.8362 23.7561C13.6751 23.9126 13.4594 24.0002 13.2347 24.0002C13.0101 24.0002 12.7943 23.9126 12.6332 23.7561L10.8452 22.0031C10.6611 21.8241 10.5147 21.6101 10.4147 21.3735C10.3147 21.137 10.2632 20.8828 10.2632 20.6261C10.2632 20.3693 10.3147 20.1151 10.4147 19.8786C10.5147 19.6421 10.6611 19.428 10.8452 19.2491L19.5512 10.7111C19.7877 10.4809 19.9756 10.2057 20.104 9.90169C20.2323 9.59768 20.2984 9.27104 20.2984 8.94106C20.2984 8.61108 20.2323 8.28444 20.104 7.98043C19.9756 7.67643 19.7877 7.40122 19.5512 7.17106L19.5012 7.12206C19.0186 6.65232 18.3718 6.38923 17.6983 6.38867C17.0248 6.38811 16.3776 6.65012 15.8942 7.11906L8.72222 14.1531L8.72022 14.1551L8.62222 14.2521C8.46109 14.4089 8.24509 14.4967 8.02022 14.4967C7.79534 14.4967 7.57934 14.4089 7.41822 14.2521C7.33935 14.1754 7.27666 14.0836 7.23386 13.9823C7.19105 13.881 7.169 13.7721 7.169 13.6621C7.169 13.5521 7.19105 13.4432 7.23386 13.3418C7.27666 13.2405 7.33935 13.1488 7.41822 13.0721L14.6912 5.93906C14.927 5.70875 15.1144 5.43357 15.2422 5.12973C15.37 4.82589 15.4357 4.49955 15.4354 4.16993C15.4351 3.84031 15.3689 3.51407 15.2405 3.21046C15.1122 2.90684 14.9244 2.63197 14.6882 2.40206Z"
                      fill="currentColor"
                    />
                    <path
                      fill-rule="evenodd"
                      clip-rule="evenodd"
                      d="M13.4852 4.76206C13.5641 4.68537 13.6268 4.59364 13.6696 4.4923C13.7124 4.39096 13.7344 4.28207 13.7344 4.17206C13.7344 4.06205 13.7124 3.95316 13.6696 3.85182C13.6268 3.75049 13.5641 3.65876 13.4852 3.58206C13.3241 3.42519 13.1081 3.33742 12.8832 3.33742C12.6583 3.33742 12.4423 3.42519 12.2812 3.58206L5.16222 10.5641C4.7679 10.9475 4.45446 11.4062 4.24044 11.9129C4.02641 12.4196 3.91614 12.964 3.91614 13.5141C3.91614 14.0641 4.02641 14.6086 4.24044 15.1152C4.45446 15.6219 4.7679 16.0806 5.16222 16.4641C5.96755 17.2474 7.04672 17.6857 8.17022 17.6857C9.29371 17.6857 10.3729 17.2474 11.1782 16.4641L18.2982 9.48206C18.3771 9.40536 18.4398 9.31364 18.4826 9.2123C18.5254 9.11096 18.5474 9.00207 18.5474 8.89206C18.5474 8.78205 18.5254 8.67316 18.4826 8.57182C18.4398 8.47048 18.3771 8.37876 18.2982 8.30206C18.1371 8.1452 17.9211 8.05742 17.6962 8.05742C17.4713 8.05742 17.2553 8.1452 17.0942 8.30206L9.97522 15.2841C9.49205 15.7543 8.84444 16.0174 8.17022 16.0174C7.49599 16.0174 6.84839 15.7543 6.36521 15.2841C6.12875 15.0539 5.9408 14.7787 5.81246 14.4747C5.68412 14.1707 5.618 13.844 5.618 13.5141C5.618 13.1841 5.68412 12.8574 5.81246 12.5534C5.9408 12.2494 6.12875 11.9742 6.36521 11.7441L13.4852 4.76206Z"
                      fill="currentColor"
                    />
                  </svg>
                </div>

                <div class="create-plugin-mcp-flow__arrow" style="--i: 5">
                  <i class="fas fa-arrow-right" />
                </div>

                <div
                  class="create-plugin-mcp-flow__badge create-plugin-mcp-flow__badge--pm"
                  style="--i: 6"
                  title="Decisions"
                >
                  <img
                    src="/img/processmaker_icon.png"
                    alt="Decisions"
                    width="22"
                    height="22"
                    class="create-plugin-mcp-flow__favicon"
                  />
                </div>
              </div>

              <div
                class="create-plugin-mcp-tips"
                :class="{ 'is-visible': mcpStage === 'tips' }"
              >
                <p class="create-plugin-mcp-tips__eyebrow">
                  {{ $t("Try prompts like") }}
                </p>
                <p class="create-plugin-mcp-tips__text">
                  <span class="create-plugin-mcp-tips__typed">{{ typedTipText }}</span>
                  <span
                    v-if="mcpStage === 'tips'"
                    class="create-plugin-mcp-tips__caret"
                  >|</span>
                </p>
              </div>
            </div>

            <fieldset class="tw-mt-4 tw-mb-0 tw-border-0 tw-p-0">
              <legend class="tw-text-sm tw-font-medium tw-text-gray-700 tw-mb-1 tw-w-auto">
                {{ $t("Project type") }}
              </legend>
              <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-x-4 tw-gap-y-1">
                <label
                  v-for="option in MCP_PROFILES"
                  :key="option.value"
                  class="tw-flex tw-items-center tw-gap-2 tw-text-sm tw-text-gray-700 tw-mb-0 tw-cursor-pointer"
                >
                  <input
                    v-model="mcpProfile"
                    type="radio"
                    name="mcp-profile"
                    :value="option.value"
                  />
                  {{ $t(option.label) }}
                </label>
              </div>
            </fieldset>

            <div class="tw-flex tw-flex-col sm:tw-flex-row tw-gap-2 tw-mt-4">
              <button
                type="button"
                class="create-plugin-copy-prompt-btn tw-flex-1"
                @click="copyMcpPrompt"
              >
                <span class="create-plugin-copy-prompt-btn__shine" aria-hidden="true" />
                <span class="create-plugin-copy-prompt-btn__label">
                  <span
                    v-if="promptCopied"
                    class="create-plugin-copy-prompt-btn__copied-icon"
                    aria-hidden="true"
                  >
                    <svg xmlns="http://www.w3.org/2000/svg" width="1.2em" height="1.2em" viewBox="0 0 24 24">
                      <circle cx="12" cy="12" r="10" fill="#16a34a" />
                      <path
                        d="M8.5 12.5l2.5 2.5 4.5-5"
                        fill="none"
                        stroke="#fff"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                      />
                    </svg>
                  </span>
                  <svg
                    v-else
                    xmlns="http://www.w3.org/2000/svg"
                    width="1.2em"
                    height="1.2em"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <g
                      fill="none"
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="1.5"
                    >
                      <rect width="12.5" height="12.5" x="8.75" y="8.75" rx="2.5" />
                      <path
                        d="M15.25 8.75v-3.5a2.5 2.5 0 0 0-2.5-2.5h-7.5a2.5 2.5 0 0 0-2.5 2.5v7.5a2.5 2.5 0 0 0 2.5 2.5h3.5"
                      />
                    </g>
                  </svg>
                  {{ promptCopied ? $t("Copied!") : $t("Copy prompt") }}
                </span>
              </button>
              <button
                type="button"
                class="tw-flex-1 tw-rounded-full tw-bg-white tw-text-gray-700 hover:tw-bg-gray-50 tw-border tw-border-gray-300 tw-px-4 tw-py-2.5 tw-text-sm tw-font-medium tw-transition-all tw-duration-200 tw-flex tw-items-center tw-justify-center tw-gap-2"
                :aria-expanded="showMcpPrompt ? 'true' : 'false'"
                @click="showMcpPrompt = !showMcpPrompt"
              >
                {{ showMcpPrompt ? $t("Hide prompt") : $t("View prompt") }}
              </button>
            </div>
            <pre
              v-if="showMcpPrompt"
              class="tw-mt-3 tw-mb-0 tw-max-h-48 tw-overflow-auto tw-rounded-lg tw-border tw-border-gray-200 tw-bg-gray-50 tw-p-3 tw-text-xs tw-text-gray-700 tw-whitespace-pre-wrap tw-break-words"
            >{{ mcpPrompt }}</pre>
          </div>
        </div>
      </div>

      <template #modal-footer>
        <div class="tw-flex tw-items-center tw-justify-end tw-w-full tw-gap-2">
          <button
            v-for="button in customModalButtons"
            :key="button.content"
            type="button"
            :disabled="button.disabled"
            :hidden="button.hidden"
            :class="createPluginFooterButtonClass(button)"
            @click="onCreatePluginFooterAction(button.action)"
          >
            {{ $t(button.content) }}
          </button>
        </div>
      </template>
    </b-modal>

    <pm-modal
      ref="pluginProgressModal"
      id="pluginProgressModal"
      :title="progressTitle"
      style="display: none"
      :set-custom-buttons="true"
      :custom-buttons="progressModalButtons"
      :hide-header-close="true"
      :no-close-on-esc="true"
      @close="hideProgressModal"
      @hidden="resetProgressModal"
    >
      <b-container class="mb-2">
        <p v-if="progressWaiting" class="text-muted mb-2">
          <i class="fas fa-spinner fa-spin" />
          {{ $t("Please wait") }}
        </p>
        <plugin-output
          :key="progressSessionKey"
          :user-id="userId"
          :waiting="progressWaiting || progressIsRunning"
          :output-title="progressOutputTitle"
          :success-message="progressSuccessMessage"
          :error-message="progressErrorMessage"
          @done="onProgressJobDone"
          @error="onProgressJobError"
          @running="onProgressJobRunning"
        />
      </b-container>
    </pm-modal>
  </div>
</template>

<script setup>
import {
  ref,
  reactive,
  computed,
  watch,
  onMounted,
  onBeforeMount,
  onUnmounted,
  getCurrentInstance,
} from "vue";
import Vuetable from "vuetable-2/src/components/Vuetable";
import Pagination from "../../components/common/Pagination";
import DataLoading from "../../components/common/DataLoading.vue";
import debounce from "lodash/debounce";
import PluginOutput from "./components/PluginOutput.vue";
import { buildMcpSetupPrompt } from "./mcpSetupPrompt";

const props = defineProps({
  permission: {
    type: [Boolean, Object, Array],
    default: false,
  },
});

const instance = getCurrentInstance();
const $t = (...args) => instance?.proxy?.$t(...args);

const createPlugin = ref(null);
const pluginProgressModal = ref(null);
const pagination = ref(null);

const plugin = reactive({
  id: null,
  url: "",
  branch: "",
  tag: "",
  gitUsername: "",
  gitToken: "",
});

const showGitCredentials = ref(false);
const showMcpPrompt = ref(false);
const MCP_PROFILES = [
  { value: "pm4", label: "PM4 only" },
  { value: "migration", label: "PM3 → PM4 migration" },
];
const mcpProfile = ref("pm4");
const promptCopied = ref(false);
const mcpFlowAnimating = ref(false);
const mcpStage = ref("idle");
const typedTipText = ref("");
let promptCopiedTimer = null;
let mcpFlowTimer = null;
let mcpTypeTimer = null;

const MCP_FLOW_STAGGER_MS = 220;
const MCP_FLOW_DURATION_MS = 750;
const MCP_FLOW_ITEMS = 7;
const MCP_FLOW_HOLD_MS = 900;
const MCP_ICONS_EXIT_MS = 450;
const MCP_TIP_HOLD_MS = 2000;
const MCP_TYPE_MS = 30;
const MCP_PROMPT_TIPS = [
  "Hey Agent! Create a process for vendor onboarding:\ncollect W-9s, route legal review, and notify procurement when approved.",
  "Optimize the process \"Invoice Approval\" and refactor its screens\nto cut duplicate fields and clarify manager/finance handoffs.",
  "Redesign the \"Expense Claim\" screen with clearer sections,\nreceipt upload, and a summary panel before submit.",
];

const clearMcpTimers = () => {
  if (mcpFlowTimer) {
    clearTimeout(mcpFlowTimer);
    mcpFlowTimer = null;
  }
  if (mcpTypeTimer) {
    clearInterval(mcpTypeTimer);
    mcpTypeTimer = null;
  }
};

const stopMcpFlowLoop = () => {
  clearMcpTimers();
  mcpFlowAnimating.value = false;
  mcpStage.value = "idle";
  typedTipText.value = "";
};

const prefersReducedMotion = () =>
  typeof window !== "undefined"
  && window.matchMedia
  && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

const scheduleMcp = (fn, ms) => {
  mcpFlowTimer = setTimeout(fn, ms);
};

const typeMcpTip = (text, onDone) => {
  typedTipText.value = "";
  let index = 0;
  mcpTypeTimer = setInterval(() => {
    index += 1;
    typedTipText.value = text.slice(0, index);
    if (index >= text.length) {
      clearInterval(mcpTypeTimer);
      mcpTypeTimer = null;
      onDone();
    }
  }, MCP_TYPE_MS);
};

const showMcpTipsPhase = () => {
  mcpStage.value = "tips";
  const runTip = (tipIndex) => {
    if (tipIndex >= MCP_PROMPT_TIPS.length) {
      typedTipText.value = "";
      mcpStage.value = "idle";
      scheduleMcp(playMcpFlowCycle, 350);
      return;
    }
    typeMcpTip(MCP_PROMPT_TIPS[tipIndex], () => {
      scheduleMcp(() => {
        typedTipText.value = "";
        scheduleMcp(() => runTip(tipIndex + 1), 180);
      }, MCP_TIP_HOLD_MS);
    });
  };
  runTip(0);
};

const playMcpFlowCycle = () => {
  if (prefersReducedMotion()) {
    mcpStage.value = "icons";
    mcpFlowAnimating.value = true;
    typedTipText.value = MCP_PROMPT_TIPS[0];
    return;
  }

  clearMcpTimers();
  typedTipText.value = "";
  mcpStage.value = "icons";
  mcpFlowAnimating.value = false;

  scheduleMcp(() => {
    mcpFlowAnimating.value = true;
    const iconsMs =
      (MCP_FLOW_ITEMS - 1) * MCP_FLOW_STAGGER_MS
      + MCP_FLOW_DURATION_MS
      + MCP_FLOW_HOLD_MS;
    scheduleMcp(() => {
      mcpStage.value = "icons-out";
      scheduleMcp(() => {
        mcpFlowAnimating.value = false;
        showMcpTipsPhase();
      }, MCP_ICONS_EXIT_MS);
    }, iconsMs);
  }, 40);
};

const filter = ref("");
const errors = ref({});
const disabled = ref(false);
const title = ref("");
const isRunning = ref(false);
const isDone = ref(false);
const customModalButtons = ref([]);
const loading = ref(true);
const data = ref([]);
const page = ref(1);
const perPage = ref(15);
const orderBy = ref("name");
const orderDirection = ref("asc");

const dataLoadingId = Math.random();
const apiDataLoading = ref(true);
const apiNoResults = ref(false);

const shouldShowLoader = computed(
  () => apiDataLoading.value || apiNoResults.value
);

const userId = computed(
  () =>
    _.get(document.querySelector('meta[name="user-id"]'), "content") || ""
);

const menuActions = [
  {
    value: "toggle-item",
    content: "Toggle Plugin",
    icon: "fas fa-toggle-on",
    ariaDescribedBy: "data.name",
  },
  {
    value: "delete-item",
    content: "Delete Plugin",
    icon: "fas fa-trash-alt",
    ariaDescribedBy: "data.id",
  },
];

const sortOrder = ref([
  {
    field: "name",
    sortField: "name",
    direction: "asc",
  },
]);

const fields = computed(() => [
  {
    title: () => $t("Name"),
    name: "__slot:name",
    sortField: "name",
  },
  {
    title: () => $t("Description"),
    name: "description",
    sortField: "description",
  },
  {
    title: () => $t("Status"),
    name: "__slot:enabled",
    sortField: "enabled",
  },
  {
    name: "__slot:actions",
    title: "",
  },
]);

const css = {
  tableClass: "table table-hover table-responsive-lg text-break mb-0",
  loadingClass: "loading",
  detailRowClass: "vuetable-detail-row",
  handleIcon: "grey sidebar icon",
  sortableIcon: "fas fa-sort",
  ascendingIcon: "fas fa-sort-up",
  descendingIcon: "fas fa-sort-down",
  ascendingClass: "ascending",
  descendingClass: "descending",
  renderIcon(classes, options) {
    return `<i class="${classes.join(" ")}"></i>`;
  },
};

const jsonRows = (rows) => {
  if (rows.length === 0 || !_.has(_.head(rows), "_json")) {
    if (!Array.isArray(rows) && typeof rows === "object") {
      return Object.values(rows);
    }
    return rows;
  }
  return rows.map((row) => JSON.parse(row._json));
};

const transform = (payload) => {
  const out = payload;
  out.meta.last_page = out.meta.total_pages;
  out.meta.from = (out.meta.current_page - 1) * out.meta.per_page;
  out.meta.to = out.meta.from + out.meta.count;
  out.data = jsonRows(out.data);
  return out;
};

const buildPagedPayload = (pluginsData) => {
  let rows = [...pluginsData];
  if (filter.value) {
    const filterLower = filter.value.toLowerCase();
    rows = rows.filter((item) => {
      const name = (item.name || "").toLowerCase();
      const status = String(item.enabled).toLowerCase();
      return (
        name.indexOf(filterLower) > -1 ||
        status.indexOf(filterLower) > -1
      );
    });
  }
  const field = orderBy.value || "name";
  const dir = orderDirection.value === "desc" ? "desc" : "asc";
  rows = _.orderBy(rows, [field], [dir]);

  const per = parseInt(perPage.value, 10);
  const total = rows.length;
  const totalPages = Math.max(1, Math.ceil(total / per));

  if (page.value > totalPages) {
    page.value = totalPages;
  }
  if (page.value < 1) {
    page.value = 1;
  }

  const from = (page.value - 1) * per;
  const pageRows = rows.slice(from, from + per);

  return {
    data: pageRows,
    meta: {
      total,
      total_pages: totalPages,
      current_page: page.value,
      per_page: per,
      count: pageRows.length,
    },
  };
};

const fetch = () => {
  loading.value = true;
  ProcessMaker.apiClient
    .get("/plugins", { baseURL: "/api/1.0/" })
    .then((response) => {
      const plugins = response.data.data || [];
      data.value = transform(buildPagedPayload(plugins));
      loading.value = false;
    })
    .catch(() => {
      loading.value = false;
    });
};

const dataManager = (sortOrderArg) => {
  const so = sortOrderArg[0];
  if (so.sortField !== undefined) {
    orderBy.value = so.sortField;
  } else {
    orderBy.value = so.field;
  }
  orderDirection.value = so.direction;
  fetch();
};

const onPaginationData = (paginationData) => {
  pagination.value?.setPaginationData(paginationData);
};

const changePerPage = (value) => {
  perPage.value = value;
  const pg = pagination.value?.tablePagination;
  const total = pg ? pg.total : 0;
  if (page.value * value > total) {
    page.value = Math.floor(total / value) + 1 || 1;
  }
  fetch();
};

const onPageChange = (p) => {
  if (p === "next") {
    page.value += 1;
  } else if (p === "prev") {
    page.value -= 1;
  } else {
    page.value = p;
  }
  if (page.value <= 0) {
    page.value = 1;
  }
  const meta = pagination.value?.tablePagination;
  if (meta && page.value > meta.last_page) {
    page.value = meta.last_page;
  }
  fetch();
};

const formatEnabled = (enabled) => {
  if (enabled === true || String(enabled).toLowerCase() === "enabled") {
    return $t("Enabled");
  }
  if (enabled === false || String(enabled).toLowerCase() === "disabled") {
    return $t("Disabled");
  }
  return enabled != null ? String(enabled) : "";
};

const getError = (name) => _.get(errors.value, `${name}.0`, false);

const isValidHttpsUrl = (value) => {
  try {
    const parsed = new URL(String(value).trim());
    return parsed.protocol === "https:";
  } catch (e) {
    return false;
  }
};

const isValidGitRefName = (value) => {
  const ref = String(value).trim();
  if (!ref) {
    return true;
  }
  if (/\s/.test(ref)) {
    return false;
  }
  if (ref.startsWith("/") || ref.endsWith("/") || ref.startsWith(".")) {
    return false;
  }
  if (ref.includes("..") || ref.includes("@{") || ref.includes("//")) {
    return false;
  }
  if (/[~^:?*[\\]/.test(ref)) {
    return false;
  }
  if (ref.endsWith(".lock") || ref.endsWith(".")) {
    return false;
  }
  return true;
};

const validatePluginForm = () => {
  const nextErrors = {};
  const url = String(plugin.url || "").trim();
  const branch = String(plugin.branch || "").trim();
  const tag = String(plugin.tag || "").trim();

  if (!url) {
    nextErrors.url = [$t("The Repository URL field is required.")];
  } else if (!isValidHttpsUrl(url)) {
    nextErrors.url = [$t("Repository URL must be a valid URL")];
  }

  if (branch && tag) {
    nextErrors.branch = [$t("Branch and tag cannot both be set.")];
    nextErrors.tag = [$t("Branch and tag cannot both be set.")];
  }

  if (branch && !isValidGitRefName(branch)) {
    nextErrors.branch = [$t("Branch must be a valid branch name")];
  }

  if (tag && !isValidGitRefName(tag)) {
    nextErrors.tag = [$t("Tag must be a valid tag name")];
  }

  errors.value = nextErrors;
  return Object.keys(nextErrors).length === 0;
};

// The Git token (plugin.gitToken) is deliberately not passed: it must never reach the prompt.
const mcpPrompt = computed(() => {
  return buildMcpSetupPrompt({
    origin: window.location.origin,
    repoUrl: plugin.url,
    branch: plugin.branch,
    username: plugin.gitUsername,
    profile: mcpProfile.value,
  });
});

const copyMcpPrompt = () => {
  const onSuccess = () => {
    promptCopied.value = true;
    ProcessMaker.alert($t("Prompt copied to clipboard"), "success");
    if (promptCopiedTimer) {
      clearTimeout(promptCopiedTimer);
    }
    promptCopiedTimer = setTimeout(() => {
      promptCopied.value = false;
    }, 2500);
  };

  if (navigator.clipboard?.writeText) {
    navigator.clipboard.writeText(mcpPrompt.value).then(onSuccess).catch(() => {
      ProcessMaker.alert($t("Clipboard functionality not available"), "danger");
    });
    return;
  }

  ProcessMaker.alert($t("Clipboard functionality not available"), "danger");
};

const initCustomModalButtons = () => {
  customModalButtons.value = [
    {
      content: "Cancel",
      action: "close",
      variant: "outline-secondary",
      disabled: false,
      hidden: false,
    },
    {
      content: "Save",
      action: "save",
      variant: "primary",
      disabled: false,
      hidden: false,
    },
    {
      content: "Close",
      action: "close",
      variant: "secondary",
      disabled: false,
      hidden: true,
    },
  ];
};

const showCreateModal = () => {
  createPlugin.value?.show();
};

const hidePluginModal = () => {
  createPlugin.value?.hide();
};

const onCreatePluginShown = () => {
  stopMcpFlowLoop();
  playMcpFlowCycle();
};

const onCreatePluginHidden = () => {
  stopMcpFlowLoop();
  resetValues();
  fetch();
};

const onCreatePluginFooterAction = (action) => {
  if (action === "save") {
    save();
    return;
  }
  hidePluginModal();
};

const createPluginFooterButtonClass = (button) => {
  if (button.action === "save") {
    return "create-plugin-footer-btn create-plugin-footer-btn--primary";
  }
  return "create-plugin-footer-btn create-plugin-footer-btn--secondary";
};

const pendingVerb = ref("installed");
const progressTitle = ref("");
const progressOutputTitle = ref("");
const progressSuccessMessage = ref("");
const progressErrorMessage = ref("");
const progressSessionKey = ref(0);
const progressIsRunning = ref(false);
const progressIsDone = ref(false);
const progressWaiting = ref(false);
const progressModalButtons = ref([]);

const save = () => {
  if (disabled.value) {
    return;
  }
  if (!validatePluginForm()) {
    return;
  }
  disabled.value = true;
  isRunning.value = true;
  isDone.value = false;
  pendingVerb.value = plugin.description ? "toggled" : "installed";
  ProcessMaker.apiClient({
    method: "POST",
    url: "/plugins/install",
    baseURL: "/api/1.0/",
    timeout: 0,
    data: {
      url: String(plugin.url || "").trim(),
      branch: String(plugin.branch || "").trim() || undefined,
      tag: String(plugin.tag || "").trim() || undefined,
      ...(plugin.gitUsername || plugin.gitToken
        ? {
            git_username: plugin.gitUsername || undefined,
            git_token: plugin.gitToken || undefined,
          }
        : {}),
    },
  })
    .then(() => {
      // Completion is driven by PluginLog events from the queued job.
      isRunning.value = true;
    })
    .catch((error) => {
      disabled.value = false;
      isRunning.value = false;
      isDone.value = true;
      errors.value = _.get(error, "response.data.errors", {});
      const message = _.get(error, "response.data.message");
      if (message) {
        ProcessMaker.alert(message, "danger");
      }
    });
};

const onPluginJobRunning = () => {
  isRunning.value = true;
  isDone.value = false;
};

const onPluginJobDone = () => {
  if (!isRunning.value) {
    return;
  }
  isRunning.value = false;
  isDone.value = true;
  disabled.value = false;
  reloadAfterPluginChange($t("The plugin was ") + pendingVerb.value + ".");
};

const onPluginJobError = () => {
  if (!isRunning.value) {
    return;
  }
  isRunning.value = false;
  isDone.value = true;
  disabled.value = false;
};

const reloadAfterPluginChange = (message) => {
  ProcessMaker.alert(message, "success", 5, true);
  window.location.reload();
};

const initProgressModalButtons = () => {
  progressModalButtons.value = [
    {
      content: "Close",
      action: "close",
      variant: "secondary",
      disabled: true,
      hidden: false,
    },
  ];
};

const openPluginProgressModal = ({
  title,
  outputTitle,
  successMessage,
  errorMessage,
  verb,
}) => {
  pendingVerb.value = verb;
  progressTitle.value = title;
  progressOutputTitle.value = outputTitle;
  progressSuccessMessage.value = successMessage;
  progressErrorMessage.value = errorMessage;
  progressSessionKey.value += 1;
  progressIsRunning.value = false;
  progressIsDone.value = false;
  progressWaiting.value = true;
  initProgressModalButtons();
  pluginProgressModal.value?.show();
};

const enableProgressModalClose = () => {
  if (progressModalButtons.value.length) {
    progressModalButtons.value[0].disabled = false;
  }
};

const onProgressJobRunning = () => {
  progressWaiting.value = false;
  progressIsRunning.value = true;
  progressIsDone.value = false;
};

const onProgressJobDone = () => {
  progressWaiting.value = false;
  progressIsRunning.value = false;
  progressIsDone.value = true;
  enableProgressModalClose();
};

const onProgressJobError = () => {
  progressWaiting.value = false;
  progressIsRunning.value = false;
  progressIsDone.value = true;
  enableProgressModalClose();
};

const hideProgressModal = () => {
  if (!progressIsDone.value) {
    return;
  }
  pluginProgressModal.value?.hide();
  fetch();
};

const resetProgressModal = () => {
  progressTitle.value = "";
  progressOutputTitle.value = "";
  progressSuccessMessage.value = "";
  progressErrorMessage.value = "";
  progressIsRunning.value = false;
  progressIsDone.value = false;
  progressWaiting.value = false;
  initProgressModalButtons();
};

const onPluginProgressApiError = (error, fallbackMessage) => {
  progressWaiting.value = false;
  progressIsRunning.value = false;
  progressIsDone.value = true;
  enableProgressModalClose();
  const message =
    _.get(error, "response.data.errors.delete.0") ||
    _.get(error, "response.data.message") ||
    fallbackMessage;
  if (message) {
    ProcessMaker.alert(message, "danger");
  }
};

const confirmDeletePlugin = (item, onConfirm) => {
  const showConfirm = ProcessMaker.confirmModal;
  if (typeof showConfirm !== "function") {
    ProcessMaker.alert($t("Unable to open confirmation dialog. Please refresh the page."), "danger");
    return;
  }
  showConfirm(
    $t("Caution!"),
    $t("Are you sure you want to delete the plugin {{ name }}?", {
      name: item.name,
    }),
    "",
    onConfirm
  );
};

const resetValues = () => {
  title.value = $t("Create Plugin");
  Object.assign(plugin, {
    id: null,
    url: "",
    branch: "",
    tag: "",
    gitUsername: "",
    gitToken: "",
  });
  showGitCredentials.value = false;
  delete plugin.description;
  errors.value = {
    url: null,
    branch: null,
    tag: null,
  };
  disabled.value = false;
  isRunning.value = false;
  isDone.value = false;
  promptCopied.value = false;
  showMcpPrompt.value = false;
  mcpProfile.value = "pm4";
  initCustomModalButtons();
};

const edit = (item) => {
  if (item.enabled !== 'Enabled') {
    return;
  }
  title.value = $t("Edit Plugin");
  Object.assign(plugin, _.cloneDeep(item));
  plugin.gitUsername = "";
  plugin.gitPassword = "";
  showGitCredentials.value = false;
  errors.value = {
    url: null,
    branch: null,
    tag: null,
  };
  disabled.value = false;
  isRunning.value = false;
  isDone.value = false;
  initCustomModalButtons();
  createPlugin.value?.show();
};

const onNavigate = (action, row) => {
  switch (action.value) {
    case "toggle-item":
      doToggle(row);
      break;
    case "delete-item":
      doDelete(row);
      break;
    default:
      break;
  }
};

const doToggle = (item) => {
  openPluginProgressModal({
    title: $t("Toggle Plugin") + `: ${item.name}`,
    outputTitle: $t("Plugin Toggle Output"),
    successMessage: $t(
      "Plugin successfully updated. You can now close this window."
    ),
    errorMessage: $t("Error toggling plugin. See output above."),
    verb: "toggled",
  });
  ProcessMaker.apiClient
    .patch(`/plugins/${item.name}/toggle`, null, {
      baseURL: "/api/1.0/",
      timeout: 0,
    })
    .then(() => {
      progressIsRunning.value = true;
    })
    .catch((error) => {
      onPluginProgressApiError(error, $t("Unable to toggle plugin."));
    });
};

const doReinstall = (item) => {
  if (item.source !== "git") {
    return;
  }
  openPluginProgressModal({
    title: $t("Update Plugin") + `: ${item.name}`,
    outputTitle: $t("Plugin Update Output"),
    successMessage: $t(
      "Plugin successfully reinstalled. You can now close this window."
    ),
    errorMessage: $t("Error reinstalling plugin. See output above."),
    verb: "reinstalled",
  });
  ProcessMaker.apiClient
    .post(`/plugins/${item.name}/reinstall`, null, {
      baseURL: "/api/1.0/",
      timeout: 0,
    })
    .then(() => {
      progressIsRunning.value = true;
    })
    .catch((error) => {
      onPluginProgressApiError(error, $t("Unable to reinstall plugin."));
    });
};

const doDelete = (item) => {
  confirmDeletePlugin(item, () => {
    openPluginProgressModal({
      title: $t("Uninstall Plugin") + `: ${item.name}`,
      outputTitle: $t("Plugin Uninstallation Output"),
      successMessage: $t(
        "Plugin successfully uninstalled. You can now close this window."
      ),
      errorMessage: $t("Error uninstalling plugin. See output above."),
      verb: "deleted",
    });
    ProcessMaker.apiClient
      .delete(`/plugins/${item.name}`, {
        baseURL: "/api/1.0/",
        timeout: 0,
      })
      .then(() => {
        progressIsRunning.value = true;
      })
      .catch((error) => {
        onPluginProgressApiError(error, $t("Unable to delete plugin."));
      });
  });
};

watch(isRunning, (val) => {
  if (val && customModalButtons.value.length >= 2) {
    customModalButtons.value[0].disabled = true;
    customModalButtons.value[1].disabled = true;
  }
});

watch(isDone, (val) => {
  if (val && customModalButtons.value.length >= 3) {
    customModalButtons.value[2].hidden = false;
    customModalButtons.value[0].hidden = true;
    customModalButtons.value[1].hidden = true;
  }
});

const debouncedOnFilter = debounce(() => {
  if (!loading.value) {
    page.value = 1;
    fetch();
  }
}, 250);

watch(filter, () => {
  debouncedOnFilter();
});

onBeforeMount(() => {
  resetValues();
});

let onApiDataLoading;
let onApiDataNoResults;

onMounted(() => {
  onApiDataLoading = (val, id) => {
    if (typeof id === "undefined" || dataLoadingId === id) {
      apiDataLoading.value = val;
    }
  };
  onApiDataNoResults = (val, id) => {
    if (typeof id === "undefined" || dataLoadingId === id) {
      apiNoResults.value = val;
    }
  };
  ProcessMaker.EventBus.$on("api-data-loading", onApiDataLoading);
  ProcessMaker.EventBus.$on("api-data-no-results", onApiDataNoResults);
  initProgressModalButtons();
  fetch();
});

onUnmounted(() => {
  if (onApiDataLoading) {
    ProcessMaker.EventBus.$off("api-data-loading", onApiDataLoading);
  }
  if (onApiDataNoResults) {
    ProcessMaker.EventBus.$off("api-data-no-results", onApiDataNoResults);
  }
  debouncedOnFilter.cancel();
  if (promptCopiedTimer) {
    clearTimeout(promptCopiedTimer);
  }
  stopMcpFlowLoop();
});
</script>

<style scoped>
.page-content {
  padding-top: 0;
}
</style>

<style>
#createPlugin .modal-dialog {
  max-width: 960px;
}

#createPlugin .create-plugin-modal {
  border-radius: 13px;
}

@media (min-width: 768px) {
  #createPlugin .create-plugin-modal__form {
    flex: 0 0 42%;
    max-width: 42%;
  }

  #createPlugin .create-plugin-modal__mcp {
    flex: 1 1 0;
    min-width: 0;
  }
}

#createPlugin .create-plugin-modal-header {
  border-bottom: 0;
  padding: 1rem 1rem 0.25rem;
}

#createPlugin .create-plugin-modal-footer {
  border-top: 0;
  padding: 0.5rem 1rem 1rem;
}

#createPlugin .create-plugin-footer-btn {
  border-radius: 9999px;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.25;
  text-transform: none;
  transition: all 0.2s ease;
}

#createPlugin .create-plugin-footer-btn--secondary {
  background: #fff;
  color: #374151;
  border: 1px solid #d1d5db;
}

#createPlugin .create-plugin-footer-btn--secondary:hover:not(:disabled) {
  background: #f9fafb;
}

#createPlugin .create-plugin-footer-btn--primary {
  background-color: var(--primary, #1572c2);
  color: #fff;
  border: 1px solid transparent;
}

#createPlugin .create-plugin-footer-btn--primary:hover:not(:disabled) {
  filter: brightness(0.95);
}

#createPlugin .create-plugin-footer-btn:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

#createPlugin .create-plugin-copy-prompt-btn {
  position: relative;
  isolation: isolate;
  overflow: hidden;
  border-radius: 9999px;
  border: 1px solid transparent;
  background:
    linear-gradient(#fff, #fff) padding-box,
    linear-gradient(120deg, #7dd3fc, #3b82f6, #08a0fa, #7dd3fc) border-box;
  background-size: 100% 100%, 220% 220%;
  color: #052d59;
  padding: 0.625rem 1rem;
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.25;
  text-transform: none;
  box-shadow:
    0 0 0 1px rgba(8, 160, 250, 0.12),
    0 0 14px rgba(8, 160, 250, 0.28);
  animation:
    create-plugin-copy-glow 2.4s ease-in-out infinite,
    create-plugin-copy-border 3.6s linear infinite;
}

#createPlugin .create-plugin-copy-prompt-btn__shine {
  position: absolute;
  inset: 0;
  border-radius: inherit;
  pointer-events: none;
  background: linear-gradient(
    110deg,
    transparent 38%,
    rgba(255, 255, 255, 0.28) 49%,
    rgba(125, 211, 252, 0.14) 51%,
    transparent 62%
  );
  transform: translateX(-130%);
  animation: create-plugin-copy-shine 5.5s ease-in-out infinite;
}

#createPlugin .create-plugin-copy-prompt-btn__label {
  position: relative;
  z-index: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

#createPlugin .create-plugin-copy-prompt-btn__copied-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  line-height: 0;
}

#createPlugin .create-plugin-copy-prompt-btn:hover {
  box-shadow:
    0 0 0 1px rgba(8, 160, 250, 0.2),
    0 0 20px rgba(8, 160, 250, 0.4);
}

@keyframes create-plugin-copy-glow {
  0%,
  100% {
    box-shadow:
      0 0 0 1px rgba(8, 160, 250, 0.12),
      0 0 12px rgba(8, 160, 250, 0.22);
  }
  50% {
    box-shadow:
      0 0 0 1px rgba(8, 160, 250, 0.22),
      0 0 22px rgba(8, 160, 250, 0.45);
  }
}

@keyframes create-plugin-copy-border {
  0% {
    background-position: 0% 50%, 0% 50%;
  }
  100% {
    background-position: 0% 50%, 220% 50%;
  }
}

@keyframes create-plugin-copy-shine {
  0%,
  45% {
    transform: translateX(-130%);
  }
  75%,
  100% {
    transform: translateX(130%);
  }
}

#createPlugin .create-plugin-input-icon {
  position: relative;
}

#createPlugin .create-plugin-input-icon__glyph {
  position: absolute;
  top: 0;
  bottom: 0;
  left: 0.75rem;
  display: inline-flex;
  align-items: center;
  color: #9ca3af;
  pointer-events: none;
  font-size: 1em;
  line-height: 0;
}

#createPlugin .create-plugin-mcp-icon {
  color: #596572;
}

#createPlugin .create-plugin-mcp-stage {
  position: relative;
  min-height: 5.75rem;
  margin-top: 1rem;
  margin-bottom: 1rem;
}

#createPlugin .create-plugin-mcp-flow {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  min-height: 3.25rem;
  transition: opacity 0.45s ease, transform 0.45s ease;
}

#createPlugin .create-plugin-mcp-flow.is-exiting,
#createPlugin .create-plugin-mcp-flow.is-hidden {
  opacity: 0;
  transform: translateY(-14px);
  pointer-events: none;
}

#createPlugin .create-plugin-mcp-tips {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  opacity: 0;
  transform: translateY(16px);
  transition: opacity 0.45s ease, transform 0.45s ease;
  pointer-events: none;
}

#createPlugin .create-plugin-mcp-tips.is-visible {
  opacity: 1;
  transform: none;
}

#createPlugin .create-plugin-mcp-tips__eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #6b7280;
}

#createPlugin .create-plugin-mcp-tips__text {
  margin: 0;
  font-size: 0.875rem;
  line-height: 1.45;
  color: #1f2937;
  white-space: pre-line;
  min-height: 2.6em;
}

#createPlugin .create-plugin-mcp-tips__caret {
  display: inline-block;
  margin-left: 1px;
  color: #3b82f6;
  font-weight: 500;
  animation: create-plugin-mcp-caret 1s steps(1) infinite;
}

@keyframes create-plugin-mcp-caret {
  0%,
  45% {
    opacity: 1;
  }
  50%,
  100% {
    opacity: 0;
  }
}

#createPlugin .create-plugin-mcp-flow__clients {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

#createPlugin .create-plugin-mcp-flow__badge {
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 0.85rem;
  border: 1px solid #e5e7eb;
  background: linear-gradient(180deg, #f9fafb 0%, #ffffff 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: #374151;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  opacity: 0;
  transform: translateY(8px) scale(0.92);
}

#createPlugin .create-plugin-mcp-flow__favicon {
  width: 22px;
  height: 22px;
  object-fit: contain;
  border-radius: 4px;
  display: block;
}

#createPlugin .create-plugin-mcp-flow__badge--mcp {
  width: 3rem;
  height: 3rem;
  border-color: #dbeafe;
  background: linear-gradient(180deg, #eff6ff 0%, #ffffff 100%);
}

#createPlugin .create-plugin-mcp-flow__badge--pm {
  width: 3rem;
  height: 3rem;
  border-color: #031e3d;
  background: linear-gradient(180deg, #031e3d 0%, #052d59 45%, #0a4a8a 100%);
}

#createPlugin .create-plugin-mcp-flow__arrow {
  color: #9ca3af;
  font-size: 0.85rem;
  opacity: 0;
  transform: translateX(-6px);
  padding: 0 0.15rem;
}

#createPlugin .create-plugin-mcp-flow.is-animating .create-plugin-mcp-flow__badge,
#createPlugin .create-plugin-mcp-flow.is-animating .create-plugin-mcp-flow__arrow {
  animation: create-plugin-mcp-pop 750ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
  animation-delay: calc(var(--i, 0) * 220ms);
}

@keyframes create-plugin-mcp-pop {
  to {
    opacity: 1;
    transform: none;
  }
}

@media (prefers-reduced-motion: reduce) {
  #createPlugin .create-plugin-mcp-flow__badge,
  #createPlugin .create-plugin-mcp-flow__arrow {
    opacity: 1;
    transform: none;
    animation: none !important;
  }

  #createPlugin .create-plugin-mcp-flow,
  #createPlugin .create-plugin-mcp-tips {
    transition: none;
  }

  #createPlugin .create-plugin-mcp-tips__caret {
    animation: none;
  }

  #createPlugin .create-plugin-copy-prompt-btn,
  #createPlugin .create-plugin-copy-prompt-btn__shine {
    animation: none;
  }
}
</style>
