<template>
  <div class="mt-4 mb-5 data-card-container low-elevation">
    <b-card class="data-card">
      <template #header>
        <div class="header">
          <i class="text-secondary data-card-header fas nav-icon d-inline align-middle" :class="info.icon" />
          <h5 class="mb-0 data-card-header d-inline align-middle">{{ info.typeHumanPlural }}</h5>
          <b-form-checkbox
            class="data-card-header export-all d-inline align-middle fw-semibold"
            v-model="includeAllByGroup"
          >
            {{ $root.operation }} All
          </b-form-checkbox>
        </div>
      </template>
      <b-card-text>
        <div class="data-card-metadata mb-1">
          <div>
            <div>
              <small v-if="hasSomeForcePasswordProtectAsset(info.items)" class="fw-semibold form-text text-muted mt-0">
                <i class="fas fa-exclamation-triangle text-warning p-0"/>
                {{ info.typeHumanPlural }} may contain sensitive information.
              </small>
            </div>
            <span>Status:</span>
            <b-badge v-if="$root.includeAllByGroup[info.type]" pill variant="success">
              <i class="fas fa-check-circle export-status-label" />
              Full {{ $root.operation }}
            </b-badge>
            <b-badge v-else pill variant="warning">
              <i class="fas fa-exclamation-triangle export-status-label" />
              Not {{ $root.operation }}ing
            </b-badge>
          </div>
        </div>
        <template v-if="$root.isImport">
          <data-tree :data="elementsCount" :collapsable="false" :show-icon="false"/>
        </template>
        <template v-else>
          Total Elements:
          <span class="fw-semibold">
            {{ info.items.length }}
            <span v-if="info.items.length > 1">{{ info.typeHumanPlural }}</span>
            <span v-else>{{ info.typeHuman }}</span>
          </span>
        </template>
        <div class="mt-3">
          <b-link v-if="$root.includeAllByGroup[info.type]" @click="onGroupDetailsClick">
            <i class="fas fa-info-circle fa-fw mr-0 pr-0"></i>
            Details
          </b-link>
        </div>
      </b-card-text>
    </b-card>
  </div>
</template>

<script>

import DataTree from "./DataTree.vue";

export default {
  components: {
    DataTree,
  },
  props: ['info'],
  mixins: [],
  data() {
    return {
    }
  },
  methods: {
    count(mode, items) {
      return items.filter(item => item.importMode === mode).length
    },
    onGroupDetailsClick() {
      window.ProcessMaker.EventBus.$emit("group-details-click", this.info.typePlural);
    },
    hasSomeForcePasswordProtectAsset(items) {
      return items.some((item) => item.forcePasswordProtect);
    },
  },
  mounted() {
  },
  computed: {
    includeAllByGroup: {
      get() {
        return this.$root.includeAllByGroup[this.info.type];
      },
      set(value) {
        this.$root.setForGroup(this.info.type, value);
      }
    },
    elementsCount() {
      return {
        label: `Total Elements:  ${this.info.items.length}`,
        isRoot: true,
        icon: "",
        children: [
          {
            label: `New Elements:  ${ this.count('copy', this.info.items) + this.count('new', this.info.items) }`,
          },
          {
            label: `Updated Elements:  ${ this.count('update', this.info.items) }`,
          },
        ],
      };
    }
  }
}
</script>

<style lang="scss" scoped>

.high-elevation {
    box-shadow: 0px 8px 10px 1px rgb(0 0 0 / 14%), 0px 3px 14px 2px rgb(0 0 0 / 12%), 0px 5px 5px -3px rgb(0 0 0 / 20%);
}

.low-elevation {
    box-shadow: 0px 1px 2px 0px rgb(60 64 67 / 25%), 0px 2px 6px 2px rgb(60 64 67 / 10%);
}

.data-card-header {
    display: inline;
}

.data-card-metadata {
    padding-left: 0;
}

.export-all {
    float: right;
}

.export-status-label {
    padding-right: 0;
}
</style>
