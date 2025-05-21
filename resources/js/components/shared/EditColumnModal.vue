<template>
  <b-modal
    ref="ModalEditColumn"
    size="lg"
    class="modal-dialog modal-dialog-centered"
    hide-footer
    scrollable
    :title="$t('Edit Task Column')"
  >
    <div class="modal-content-custom">
      <column-chooser
        v-model="columns.currentColumns"
        :available-columns="columns.availableColumns"
        :default-columns="columns.defaultColumns"
        :data-columns="columns.dataColumns"
      >
        <template #title1>
          <small class="form-text text-muted">
            <a
              href="#"
              @click.prevent="$refs['ModalEditColumn'].hide()"
            >
              <i class="fp-arrow-left" />
              {{ $t("Go back to Launchpad Settings") }}
            </a>
          </small>
        </template>
        <template #footer>
          <b-button
            variant="outline-secondary"
            class="mr-1"
            @click="$refs['ModalEditColumn'].hide()"
          >
            {{ $t("Cancel and go back") }}
          </b-button>
          <b-button
            variant="secondary"
            @click="saveColumns"
          >
            {{ $t("Save columns") }}
          </b-button>
        </template>
      </column-chooser>
    </div>
  </b-modal>
</template>

<script>
import ColumnChooser from "./ColumnChooser.vue";

export default {
  components: {
    ColumnChooser,
  },
  props: {
    dataColumns: {
      type: Object,
      default: () => {},
    },
    type: {
      type: String,
      default: () => "",
    },
  },
  data() {
    return {};
  },
  computed: {
    columns() {
      return this.dataColumns;
    },
  },
  methods: {
    showModal() {
      this.$refs.ModalEditColumn.show();
    },
    saveColumns() {
      this.$emit("updateColumns", this.columns.currentColumns, this.type);
      this.$refs.ModalEditColumn.hide();
    },
  },
};
</script>
