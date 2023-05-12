<template>
  <div class="data-table">
    <div v-if="!loading && !translatedLanguages.length">
      <div class="d-flex flew-grow-1 flex-column align-items-center no-results-container">
        <div class="icon-lg text-secondary">
          <font-awesome-icon :icon="['fpm', 'fa-translations']" />
        </div>
        <div class="text-secondary">
          {{ $t("No translations found") }}
        </div>
      </div>
    </div>

    <table v-if="!loading && translatedLanguages.length" id="table-translations" class="table table-hover table-responsive-lg ">
      <thead>
        <tr>
            <th class="notify">{{ $t('Target Language') }}</th>
            <th class="action">{{ $t('Created') }}</th>
            <th class="action">{{ $t('Updated') }}</th>
            <th class="action"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in translatedLanguages" :key="index">
          <td class="notify">{{ item.humanLanguage }}</td>
          <td class="action">{{ item.createdAt }}</td>
          <td class="action">{{ item.updatedAt }}</td>
          <td class="action">
            <ellipsis-menu
              :actions="actions"
              :permission="permission"
              :data="item"
              :divider="true"
              @navigate="onNavigate"
            />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import { createUniqIdsMixin } from "vue-uniq-ids";
import { library } from "@fortawesome/fontawesome-svg-core";
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";
import { faTranslations } from "../../../components/shared/customIcons/faTranslations";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu },
  mixins: [uniqIdsMixin],
  props: ["filter", "id", "status", "permission", "processId"],
  data() {
    return {
      translatedLanguages: null,
      editTranslation: null,
      orderBy: "language",
      loading: false,
      sortOrder: [
        {
          field: "name",
          sortField: "name",
          direction: "asc",
        },
      ],
      actions: [
        {
          value: "edit-translation", content: "Edit Translation", link: false, href: "", permission: "edit-process-translation", icon: "fas fa-edit",
        },
        {
          value: "export-translation", content: "Export Translation", permission: "export-process-translation", icon: "fas fa-file-export",
        },
        {
          value: "delete-translation", content: "Delete Translation", permission: "delete-process-translation", icon: "fas fa-trash",
        },
      ],
    };
  },

  watch: {
    filter() {
      this.fetch();
    },
  },

  created() {
    // Add custom translations icon
    library.add(faTranslations);

    this.fetch();
    ProcessMaker.EventBus.$on("api-data-process-translations", () => {
      this.fetch();
    });
  },

  methods: {
    onNavigate(action, data, index) {
      switch (action.value) {
        case "edit-translation":
          this.handleEditTranslation(data);
          break;
        case "export-translation":
          // this.exportTranslation(data);
          break;
        case "delete-translation":
          this.handleDeleteTranslation(data);
          break;
        default:
          break;
      }
    },

    fetch() {
      this.loading = true;

      const url = "process/translations?process_id=" + this.processId;

      // Load from our api client
      ProcessMaker.apiClient
        .get(
          url +
          "&page=" +
          this.page +
          "&per_page=" +
          this.perPage +
          "&filter=" +
          this.filter +
          "&order_by=" +
          this.orderBy +
          "&order_direction=" +
          this.orderDirection +
          "&include="
        )
        .then((response) => {
          this.translatedLanguages = response.data.translatedLanguages;
          this.$emit("translated-languages-changed", this.translatedLanguages);
          this.loading = false;
        });
    },

    handleEditTranslation(data) {
      this.editTranslation = data;
      this.$emit("edit-translation", this.editTranslation);
    },

    handleDeleteTranslation(translation) {
      console.log(translation);
      ProcessMaker.confirmModal(
        this.$t("Caution!"),
        this.$t(`Are you sure you want to delete the translations for ${translation.humanLanguage} language?`),
        "",
        () => {
          ProcessMaker.apiClient
            .delete(`/process/translations/${translation}`)
            .then(() => {
            });
        },
      );
    },
  },
};
</script>

<style lang="scss" scoped>
  .icon-lg {
    font-size: 5rem;
  }

  .no-results-container {
    padding: 8rem 0rem;
  }

  :deep(th#_updated_at) {
    width: 14%;
  }

  :deep(th#_created_at) {
    width: 14%;
  }
  .process-template-table-card {
    padding: 0;
  }
</style>
