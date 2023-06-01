<template>
  <div>
    <b-modal
      id="showLogInfo"
      ref="log-info"
      hide-header-close
      centered
    >
      <template #modal-title>
        <p class="text-capitalize font-weight-bold">
          {{ $t(titleModal) }}
        </p>
        <span class="title-occurred">
          {{ $t('At') }} {{ occurredAt }}
        </span>
      </template>
      <div class="body-modal">
        <div
          v-for="(info, key) in infoData"
          :key="key"
        >
          <template v-if="typeof info === 'string'">
            <p>
              <b>{{ $t(key) }}:</b>
              <span>{{ info }}</span>
            </p>
          </template>
          <template v-if="info.link && info.label">
            <p class="link-value">
              <b>{{ $t(key) }}:</b>
              <a :href="info.link">
                {{ info.label }}
              </a>
            </p>
          </template>
          <template v-if="info.old">
            <p class="old-value">
              <b>- {{ $t(key) }}:</b>
              <span>{{ info.old }}</span>
            </p>
          </template>
          <template v-if="info.new">
            <p class="new-value">
              <b>+ {{ $t(key) }}:</b>
              <span>{{ info.new }}</span>
            </p>
          </template>
        </div>
      </div>
      <template #modal-footer>
        <b-button @click="closeModal">
          {{ $t('Close') }}
        </b-button>
      </template>
    </b-modal>
  </div>
</template>

<script>
export default {
  props: ["data"],
  data() {
    return {
      titleModal: "",
      occurredAt: "",
      infoData: {},
    };
  },
  methods: {
    /**
     * Show modal with the information requested
     */
    showLogInfo(data) {
      this.titleModal = data.rowData.event;
      this.occurredAt = data.rowData.occurred_at;
      this.infoData = this.formatData(data.rowData.data);
      this.$refs["log-info"].show();
    },
    /**
     * Close modal
     */
    closeModal() {
      this.titleModal = "";
      this.occurredAt = "";
      this.infoData = {};
      this.$refs["log-info"].hide();
    },
    /**
     * Format the data to a JSON
     */
    formatData(data) {
      let key = "";
      let value = "";
      let auxKey = "";
      const auxArray = {};

      for ([key, value] of Object.entries(data)) {
        if (key.startsWith("+")) {
          auxKey = key.split(" ")[1];
          if (auxArray[auxKey]) {
            auxArray[auxKey].new = value.toString();
          } else {
            auxArray[auxKey] = {
              new: value.toString(),
            };
          }
        } else if (key.startsWith("-")) {
          auxKey = key.split(" ")[1];
          if (auxArray[auxKey]) {
            auxArray[auxKey].old = value.toString();
          } else {
            auxArray[auxKey] = {
              old: value.toString(),
            };
          }
        } else {
          auxArray[key] = typeof value === 'boolean' ? value.toString() : value;
        }
      }
      return auxArray;
    },
  },
};
</script>

<style scoped>
.body-modal {
  white-space: pre-line;
}
.title-occurred {
  font-size: 12px;
}
.modal-title p {
  margin-top: 0;
  margin-bottom: 0;
}
.old-value {
  background-color: #ffeeec;
}
.new-value {
  background-color: #e9ffee;
}
.link-value {
  white-space: nowrap
}
p {
  word-break: break-all;
}
</style>
