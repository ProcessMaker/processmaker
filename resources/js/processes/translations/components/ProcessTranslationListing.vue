<template>
  <div class="data-table">
    <div v-if="!loading && !translatedLanguages.length">
      <div class="d-flex flew-grow-1 flex-column align-items-center no-results-container">
        <div class="icon-lg text-secondary">
          <i class="fa fa-language" />
        </div>
        <div class="text-secondary">
          {{ $t("No translations have been created for this process") }}
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
          <td class="notify">{{ item.human_language }}</td>
          <td class="action">{{ item.created_at }}</td>
          <td class="action">{{ item.updated_at }}</td>
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
import EllipsisMenu from "../../../components/shared/EllipsisMenu.vue";

const uniqIdsMixin = createUniqIdsMixin();

export default {
  components: { EllipsisMenu },
  mixins: [uniqIdsMixin],
  props: ["filter", "id", "status", "permission", "processId"],
  data() {
    return {
      translatedLanguages: null,
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
          value: "edit-translation", content: "Edit Translation", link: true, href: "/modeler/translation/{{id}}", permission: "edit-process-translation", icon: "fas fa-edit",
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
    this.fetch();
    ProcessMaker.EventBus.$on("api-data-process-translations", () => {
      this.fetch();
    });
  },

  methods: {
    onNavigate(action, data, index) {
      switch (action.value) {
        case "edit-translation":
          // this.goToTranslation(data.id);
          break;
        case "export-translation":
          // this.exportTranslation(data);
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
