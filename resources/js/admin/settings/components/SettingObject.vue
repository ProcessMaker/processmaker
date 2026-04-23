<template>
  <div class="setting-object">
    <div class="setting-object-value">
      <div v-if="text">
        {{ trimmed(text) }}
      </div>
      <div
        v-else
        class="font-italic text-black-50"
      >
        Empty
      </div>
    </div>
    <b-modal
      v-model="showModal"
      class="setting-object-modal"
      size="lg"
      @hidden="onModalHidden"
    >
      <template
        #modal-header
        class="d-block"
      >
        <div>
          <h5
            v-if="setting.name"
            class="mb-0"
          >
            {{ $t(setting.name) }}
          </h5>
          <h5
            v-else
            class="mb-0"
          >
            {{ setting.key }}
          </h5>
          <small
            v-if="setting.helper"
            class="form-text text-muted"
          >{{ $t(setting.helper) }}</small>
        </div>
        <button
          type="button"
          :aria-label="$t('Close')"
          class="close"
          @click="onCancel"
        >
          ×
        </button>
      </template>
      <div
        v-if="!ui('single')"
        class="position-absolute w-100 ml-n3 d-flex"
      >
        <div class="w-75" />
        <div class="w-25 ml-n3 d-flex justify-content-end">
          <b-button
            class="setting-add-button"
            variant="secondary"
            size="sm"
            @click="onAdd()"
          >
            <i class="fa fa-plus" /> Add
          </b-button>
        </div>
      </div>
      <b-table
        class="setting-object-table"
        :items="table"
        :fields="fields"
        striped
      >
        <template #cell(key)="data">
          <div v-if="ui('fixedKeys') === false">
            <div class="d-flex w-100 flex-wrap">
              <b-form-input
                v-model="data.item.key.name"
                class="ml-2 mr-2"
                :class="{'is-invalid': !data.item.isValid}"
                spellcheck="false"
                autocomplete="off"
                @keyup.enter="onSave()"
              />
              <small
                v-if="!data.item.isValid"
                class="form-text text-danger mx-2"
              >
                {{ $t('Invalid variable name') }}
              </small>
            </div>
          </div>
          <div v-else>
            <multiselect
              v-model="data.item.key"
              :options="availableKeys"
              track-by="name"
              label="name"
              aria-label="name"
              :show-labels="false"
              class="setting-object-multiselect ml-2 mr-2"
            />
          </div>
        </template>
        <template #cell(value)="data">
          <div class="d-flex w-100">
            <b-form-input
              v-model="data.item.value"
              class="mr-2"
              spellcheck="false"
              autocomplete="off"
              @keyup.enter="onSave()"
            />
            <b-button
              v-if="!ui('single')"
              :aria-label="$t('Delete')"
              class="mr-2"
              variant="link"
              @click="onDelete(data)"
            >
              <i class="fa fa-trash" />
            </b-button>
          </div>
        </template>
      </b-table>
      <div
        slot="modal-footer"
        class="w-100 m-0 d-flex"
      >
        <button
          type="button"
          class="btn btn-outline-secondary ml-auto"
          @click="onCancel"
        >
          {{ $t('Cancel') }}
        </button>
        <button
          type="button"
          class="btn btn-secondary ml-3"
          :disabled="! changed || haveInvalid"
          @click="onSave"
        >
          {{ $t('Save') }}
        </button>
      </div>
    </b-modal>
  </div>
</template>

<script>
import settingMixin from "../mixins/setting";

export default {
  mixins: [settingMixin],
  props: ["value", "setting"],
  data() {
    return {
      input: null,
      keys: null,
      showModal: false,
      table: [],
      transformed: null,
      haveInvalid: false,
      fields: [
        {
          key: "key",
          label: this.keyLabel(),
          sortable: false,
          thClass: "thKey",
        },
        {
          key: "value",
          label: this.valueLabel(),
          sortable: false,
          thClass: "thValue",
        },
      ],
    };
  },
  computed: {
    availableKeys() {
      const available = this.copy(this.keys);
      this.table.forEach((item) => {
        if (item.key && item.key.name) {
          const disable = available.find((key) => key.name === item.key.name);
          if (disable) {
            disable.$isDisabled = true;
          }
        }
      });
      return available;
    },
    changed() {
      if (_.isEmpty(this.input) && _.isEmpty(this.transformed)) {
        return false;
      }
      return JSON.stringify(this.input) !== JSON.stringify(this.transformed);
    },
    text() {
      const lines = [];
      if (this.input && typeof this.input === "object") {
        Object.keys(this.input).forEach((key) => {
          if (this.input[key] !== null) {
            lines.push(`${key} → ${this.input[key]}`);
          }
        });
      }
      if (lines.length) {
        return lines.join(", ");
      }
      return "";
    },
  },
  watch: {
    value: {
      handler(value) {
        this.input = value;
        this.setTable();
      },
    },
    table: {
      handler(value) {
        this.transformed = this.copy(this.input);
        if (this.ui("single")) {
          this.transformed = {};
          this.keys.forEach((key) => {
            const match = value.find((item) => item.key.name == key.name);
            if (match) {
              this.transformed[key.name] = match.value;
            }
          });
        } else if (this.ui("fixedKeys")) {
          this.keys.forEach((key) => {
            const match = value.find((item) => item.key.name == key.name);
            if (match) {
              this.transformed[key.name] = match.value;
            } else {
              this.transformed[key.name] = null;
            }
          });
        } else {
          this.transformed = {};
          this.table.forEach((row) => {
            if (row.key.name && row.key.name.length && row.value && row.value.length) {
              this.transformed[row.key.name] = row.value;
            }
          });
          this.haveInvalid = false;
          value.forEach((item) => {
            if (item.key) {
              item.isValid = this.isValid(item.key.name);
              if (!item.isValid) {
                this.haveInvalid = true;
              }
            }
          });
        }
      },
      deep: true,
    },
  },
  mounted() {
    if (this.value === null) {
      this.input = "";
    } else {
      this.input = this.copy(this.value);
    }
    this.setTable();
  },
  methods: {
    isValid(key) {
      const pattern = /^[a-zA-Z_][a-zA-Z0-9_]*$/g;
      return pattern.test(key) && key != "";
    },
    keyLabel() {
      if (this.ui("keyLabel")) {
        return this.$t(this.ui("keyLabel"));
      }
      return this.$t("Key");
    },
    valueLabel() {
      if (this.ui("valueLabel")) {
        return this.$t(this.ui("valueLabel"));
      }
      return this.$t("Value");
    },
    onAdd(data) {
      if (data) {
        this.table.splice(data.index + 1, 0, {
          key: {
            name: null,
            $isDisabled: false,
          },
          value: null,
        });
      } else {
        this.table.push({
          key: {
            name: null,
            $isDisabled: false,
          },
          value: null,
          isValid: false,
        });
      }
    },
    onDelete(data) {
      this.table.splice(data.index, 1);
    },
    onEdit() {
      this.showModal = true;
    },
    onCancel() {
      this.showModal = false;
    },
    onModalHidden() {
      this.transformed = this.copy(this.input);
      this.setTable();
    },
    onSave() {
      this.input = this.copy(this.transformed);
      this.showModal = false;
      this.emitSaved(this.input);
    },
    setTable() {
      this.table = [];
      this.keys = Object.keys(this.input).map((key) => ({
        name: key,
        $isDisabled: false,
      }));
      this.keys.forEach((key) => {
        if (this.input[key.name] !== null && this.input[key.name].length) {
          this.table.push({
            key,
            value: this.input[key.name],
            isValid: this.isValid(key.name),
          });
        }
      });
    },
  },
};
</script>

<style lang="scss">
  @import '../../../../sass/colors';

  $disabledBackground: lighten($secondary, 20%);
  $multiselect-height: 38px;

  .setting-add-button {
    margin-top: 5px;
  }

  .setting-object-table th,
  .setting-object-table td {
    padding: 8px 0;
  }

  .thKey {
    width: 300px !important;
  }

  .setting-object-multiselect {
    &.multiselect {
      display: inline-block !important;
      max-width: 300px;
      width: 300px;
    }

    .multiselect,
    .multiselect__tags {
      height: $multiselect-height;
      min-height: $multiselect-height;
      max-height: $multiselect-height;
    }

    .multiselect__placeholder {
      display: block;
      line-height: 20px;
      margin: 0;
      margin-bottom: 10px;
      padding-bottom: 2px;
      padding-left: 5px;
      padding-top: 0;
    }

    .multiselect__single {
      padding-bottom: 2px;
      padding-top: 0;
    }

    .multiselect__tags {
      font-size: 16px;
    }

    .multiselect__option--highlight {
      background: #ddd;
      color: #222;
    }

    .multiselect__option--disabled {
      background: none !important;
      color: #ccc;
    }

    .form-control-multiselect {
      position: relative;
      -webkit-box-flex: 1;
          -ms-flex: 1 1 0%;
              flex: 1 1 0%;
      min-width: 0;
      margin-bottom: 0;
    }

  }
</style>
