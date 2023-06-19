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
          <template v-if="isString(info)">
            <p>
              <b>{{ $t(key) }}:</b>
              <span>{{ info }}</span>
            </p>
          </template>
          <template v-if="isLink(info)">
            <p class="link-value">
              <b>{{ $t(key) }}:</b>
              <a :href="info.link">
                {{ info.label }}
              </a>
            </p>
          </template>
          <template v-if="hasOldValue(info)">
            <p class="old-value">
              <b>- {{ $t(key) }}:</b>
              <template v-if="!isString(info.old)">
                <div class="subItem">
                  <template
                    v-for="(subInfo, skey) in info.old"
                  >
                    <span
                      v-if="isLink(subInfo)"
                      :key="skey"
                    >
                      <a :href="subInfo.link">
                        {{ subInfo.label }}
                      </a>
                    </span>
                    <span
                      v-if="isString(subInfo)"
                      :key="skey"
                    >
                      {{ subInfo }}
                    </span>
                  </template>
                </div>
              </template>
              <span v-if="isString(info.old)">{{ info.old }}</span>
            </p>
          </template>
          <template v-if="hasNewValue(info)">
            <p class="new-value">
              <b>+ {{ $t(key) }}:</b>
              <template v-if="isCollection(info.new)">
                <div class="subItem">
                  <template
                    v-for="(subInfo, skey) in info.new"
                  >
                    <span
                      v-if="isLink(subInfo)"
                      :key="skey"
                    >
                      <a :href="subInfo.link">
                        {{ subInfo.label }}
                      </a>
                    </span>
                    <span
                      v-if="isString(subInfo)"
                      :key="skey"
                    >
                      {{ subInfo }}
                    </span>
                  </template>
                </div>
              </template>
              <span
                v-if="isLink(info.new)"
                class="link-value"
              >
                <a :href="info.new.link">
                  {{ info.new.label }}
                </a>
              </span>
              <span v-if="isString(info.new)">{{ info.new }}</span>
            </p>
          </template>
          <template v-if="isCollectionLink(info)">
            <p>
              <b>{{ $t(key) }}:</b>
              <template v-for="(subInfo, skey) in info">
                <p
                  v-if="isLink(subInfo)"
                  :key="skey"
                  class="link-value"
                >
                  <a :href="subInfo.link">
                    {{ subInfo.label }}
                  </a>
                </p>
                <p
                  v-if="isString(subInfo)"
                  :key="skey"
                >
                  {{ subInfo }}
                </p>
              </template>
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
            auxArray[auxKey].new = this.booleanToString(value);
          } else {
            auxArray[auxKey] = {
              new: this.booleanToString(value),
            };
          }
        } else if (key.startsWith("-")) {
          auxKey = key.split(" ")[1];
          if (auxArray[auxKey]) {
            auxArray[auxKey].old = this.booleanToString(value);
          } else {
            auxArray[auxKey] = {
              old: this.booleanToString(value),
            };
          }
        } else {
          auxArray[key] = this.booleanToString(value);
        }
      }
      return auxArray;
    },
    /**
     * Verify if value is a string o null
     */
    isString(value) {
      return typeof value === "string" || typeof value === "number" || value === null;
    },
    /**
     * Verify if value has link and label
     */
    isLink(value) {
      if (value !== null && typeof value === "object") {
        return value.link && value.label;
      }
      return false;
    },
    /**
     * Verify if value is an array
     */
    isCollection(value) {
      return Array.isArray(value);
    },
    /**
     * Verify if value is an array and children has link
     */
    isCollectionLink(value) {
      const auxValue = structuredClone(value);
      return Array.isArray(auxValue) && this.isLink(auxValue.shift());
    },
    /**
     * Convert boolean value's to string
     */
    booleanToString(value) {
      return typeof value === "boolean" ? value.toString() : value;
    },
    /**
     * Verify if value has old value
     */
    hasOldValue(value) {
      if (value !== null) {
        return value.old;
      }
      return false;
    },
    /**
     * Veify if value has new value
     */
    hasNewValue(value) {
      if (value !== null) {
        return value.new;
      }
      return false;
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
.subItem {
  display: block;
  padding-left: 10%;
  margin-top: -3%;
}
.subItem span {
  display: contents;
}
</style>
