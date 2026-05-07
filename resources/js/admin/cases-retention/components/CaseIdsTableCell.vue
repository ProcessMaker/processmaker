<template>
  <div
    v-uni-id="'case-id-' + rowId"
    class="case-ids-cell"
  >
    <template v-if="!ids.length">
      —
    </template>
    <template v-else-if="!hasOverflow">
      <span class="case-ids-preview">{{ ids.join(", ") }}</span>
    </template>
    <template v-else>
      <button
        :id="popoverTriggerId"
        type="button"
        class="case-ids-trigger"
        :title="$t('Show full list')"
      >
        <span class="case-ids-preview">{{ previewHead }}</span><span class="case-ids-ellipsis">…</span><span class="case-ids-more-count text-muted">+{{ moreHiddenCount }}</span>
      </button>
      <b-popover
        :target="popoverTriggerId"
        triggers="click"
        placement="auto"
        boundary="viewport"
        container="body"
        custom-class="case-ids-popover"
      >
        <template #title>
          {{ $t("Case IDs") }}
          <span class="text-muted font-weight-normal">({{ ids.length }})</span>
        </template>
        <div class="case-ids-popover-inner">
          <pre class="case-ids-popover-pre mb-0">{{ fullListText }}</pre>
        </div>
      </b-popover>
    </template>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  name: "CaseIdsTableCell",
  mixins: [uniqIdsMixin],
  props: {
    caseIds: {
      type: [Array, String, Number],
      default: null,
    },
    rowId: {
      type: [Number, String],
      required: true,
    },
    previewLimit: {
      type: Number,
      default: 5,
    },
  },
  computed: {
    ids() {
      return this.parseCaseIdsArray(this.caseIds);
    },
    hasOverflow() {
      return this.ids.length > this.previewLimit;
    },
    previewHead() {
      return this.ids.slice(0, this.previewLimit).join(", ");
    },
    moreHiddenCount() {
      return this.ids.length - this.previewLimit;
    },
    fullListText() {
      return this.ids.join(", ");
    },
    popoverTriggerId() {
      return `retention-case-ids-pop-${this.rowId}`;
    },
  },
  methods: {
    parseCaseIdsArray(caseIds) {
      if (caseIds == null || caseIds === "") {
        return [];
      }
      if (Array.isArray(caseIds)) {
        return caseIds.map(String);
      }
      if (typeof caseIds === "string") {
        try {
          const parsed = JSON.parse(caseIds);
          return Array.isArray(parsed) ? parsed.map(String) : [];
        } catch {
          return [];
        }
      }
      return [String(caseIds)];
    },
  },
};
</script>

<style lang="scss" scoped>
.case-ids-preview {
  color: #4e5663;
}

.case-ids-trigger {
  cursor: pointer;
  border: none;
  background: transparent;
  font: inherit;
  color: inherit;
  border-radius: 4px;
  margin: -2px -4px;
  padding: 2px 4px;
  display: inline;
  text-align: left;
  line-height: inherit;
  vertical-align: baseline;

  &:hover,
  &:focus {
    background-color: rgba(0, 0, 0, 0.04);
  }

  &:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.25);
  }
}

.case-ids-ellipsis {
  color: #4e5663;
  letter-spacing: 0.02em;
}

.case-ids-more-count {
  font-size: 12px;
  margin-left: 2px;
  font-weight: 500;
  white-space: nowrap;
}
</style>

<!-- Popover is teleported to body; scoped styles do not apply. -->
<style lang="scss">
.popover.case-ids-popover {
  max-width: min(440px, 92vw);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.12);
}

.popover.case-ids-popover .popover-body {
  padding: 0;
}

.case-ids-popover-inner {
  max-height: min(320px, 50vh);
  overflow: auto;
  padding: 0.75rem 1rem;
}

.case-ids-popover-pre {
  white-space: pre-wrap;
  word-break: break-all;
  font-size: 13px;
  line-height: 1.45;
  color: #4e5663;
}
</style>
