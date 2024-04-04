export default {
  data() {
    return {
      commonFields: [
        {
          label: this.$t("Name"),
          field: "name",
          width: 200,
          sortable: true,
          truncate: true,
          direction: "none",
        },
        {
          label: this.$t("Description"),
          field: "description",
          width: 200,
          truncate: true,
          sortable: true,
          direction: "none",
          sortField: "description",
        },
        {
          label: this.$t("Type of Screen"),
          field: "screen_type",
          width: 160,
          sortable: true,
          direction: "none",
          sortField: "screen_type",
        },
        {
          label: this.$t("Modified"),
          field: "updated_at",
          format: "datetime",
          width: 160,
          sortable: true,
          direction: "none",
        },
      ],
    };
  },
  methods: {
    insertFieldAfter(fieldName, newField) {
      const index = this.commonFields.findIndex((field) => field.field === fieldName);
      if (index !== -1) {
        this.commonFields.splice(index + 1, 0, newField);
      }
    },
  },
};
