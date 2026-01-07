<template>
  <aside class="tw-w-72 tw-shrink-0">
    <div class="tw-rounded-xl tw-border tw-border-zinc-200 tw-p-3 tw-bg-white tw-h-screen">
      <div class="tw-px-2 tw-py-1 tw-text-xs tw-font-semibold tw-uppercase tw-text-zinc-500">
        Logs
      </div>
      <nav class="tw-mt-1">
        <!-- Email Category - Only show if email package is installed -->
        <div
          v-if="hasEmailPackage"
          class="tw-mt-3"
        >
          <button
            v-b-toggle.collapse-email
            class="
              tw-w-full
              tw-flex
              tw-items-center
              tw-justify-between
              tw-rounded-lg
              tw-px-3
              tw-py-2
              tw-text-sm
              tw-font-medium
              tw-text-zinc-700
              hover:tw-bg-zinc-50
              tw-cursor-pointer
              tw-transition-colors
            "
          >
            <span class="tw-inline-flex tw-items-center tw-gap-2">
              <i class="fas fa-envelope" />
              Email
            </span>
            <i
              class="fas fa-chevron-right tw-w-4 tw-h-4 tw-transition-transform"
              :class="{ 'tw-rotate-90': collapseStates.email }"
            />
          </button>

          <!-- Email Logs Subcategory -->
          <b-collapse
            id="collapse-email"
            class="tw-ml-4 tw-mt-1"
            :visible="isEmailActive"
          >
            <RouterLink
              to="/email/errors"
              class="
                tw-mt-1
                tw-flex
                tw-items-center
                tw-justify-between
                tw-rounded-lg
                tw-px-3
                tw-py-2
                tw-text-base
                tw-cursor-pointer
              "
              :class="emailLinkClasses"
            >
              <span class="tw-inline-flex tw-items-center tw-gap-2">Email Start Event</span>
            </RouterLink>
          </b-collapse>
        </div>

        <!-- Agents Category - Only show if AI package is installed -->
        <div
          v-if="hasAiPackage"
          class="tw-mt-3"
        >
          <button
            v-b-toggle.collapse-agents
            class="
              tw-w-full
              tw-flex
              tw-items-center
              tw-justify-between
              tw-rounded-lg
              tw-px-3
              tw-py-2
              tw-text-sm
              tw-font-medium
              tw-text-zinc-700
              hover:tw-bg-zinc-50
              tw-cursor-pointer
              tw-transition-colors
            "
          >
            <span class="tw-inline-flex tw-items-center tw-gap-2">
              <i class="fas fa-robot" />
              FlowGenie Agents
            </span>
            <i
              class="fas fa-chevron-right tw-w-4 tw-h-4 tw-transition-transform"
              :class="{ 'tw-rotate-90': collapseStates.agents }"
            />
          </button>

          <!-- Agents Logs Subcategory -->
          <b-collapse
            id="collapse-agents"
            class="tw-ml-4 tw-mt-1"
            :visible="isAgentsActive"
          >
            <RouterLink
              to="/agents"
              class="
                tw-mt-1
                tw-flex
                tw-items-center
                tw-justify-between
                tw-rounded-lg
                tw-px-3
                tw-py-2
                tw-text-base
                tw-cursor-pointer
              "
              :class="agentsLinkClasses"
            >
              <span class="tw-inline-flex tw-items-center tw-gap-2">FlowGenie Agents logs</span>
            </RouterLink>
          </b-collapse>
        </div>
      </nav>
    </div>
  </aside>
</template>

<script>
import { hasEmailPackage, hasAiPackage } from "../routes";

export default {
  name: "LogsSidebar",
  data() {
    return {
      collapseStates: {
        email: this.$route.path.startsWith("/email"),
        agents: this.$route.path.startsWith("/agents"),
      },
    };
  },
  computed: {
    hasEmailPackage() {
      return hasEmailPackage();
    },
    hasAiPackage() {
      return hasAiPackage();
    },
    isEmailActive() {
      return this.$route.path.startsWith("/email");
    },
    isAgentsActive() {
      return this.$route.path.startsWith("/agents");
    },
    emailLinkClasses() {
      return this.isEmailActive
        ? "tw-bg-blue-50 tw-font-semibold tw-text-blue-500"
        : "tw-font-medium tw-text-zinc-700 hover:tw-bg-zinc-50";
    },
    agentsLinkClasses() {
      return this.isAgentsActive
        ? "tw-bg-blue-50 tw-font-semibold tw-text-blue-500"
        : "tw-font-medium tw-text-zinc-700 hover:tw-bg-zinc-50";
    },
  },
  watch: {
    // Auto-expand the category when navigating to it
    "$route.path": {
      handler(path) {
        if (path.startsWith("/agents") && !this.collapseStates.agents) {
          this.$root.$emit("bv::toggle::collapse", "collapse-agents");
        }
        if (path.startsWith("/email") && !this.collapseStates.email) {
          this.$root.$emit("bv::toggle::collapse", "collapse-email");
        }
      },
      immediate: true,
    },
  },
  mounted() {
    // Listen for collapse events to update icon rotation
    this.$root.$on("bv::collapse::state", (id, state) => {
      if (id === "collapse-email") {
        this.collapseStates.email = state;
      } else if (id === "collapse-agents") {
        this.collapseStates.agents = state;
      }
    });
  },
  beforeDestroy() {
    // Clean up event listener
    this.$root.$off("bv::collapse::state");
  },
};
</script>
