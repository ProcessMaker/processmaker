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
        {
          name: "__slot:actions",
          field: "actions",
          width: 60,
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
    handleEllipsisClick(templateColumn) {
      this.fields.forEach(column => {
        if (column.field !== templateColumn.field) {
          column.direction = "none";
          column.filterApplied = false;
        }
      });

      if (templateColumn.direction === "asc") {
        templateColumn.direction = "desc";
      } else if (templateColumn.direction === "desc") {
        templateColumn.direction = "none";
        templateColumn.filterApplied = false;
      } else {
        templateColumn.direction = "asc";
        templateColumn.filterApplied = true;
      }

      if (templateColumn.direction !== "none") {
        const sortOrder = [
          {
          sortField: templateColumn.sortField || templateColumn.field,
          direction: templateColumn.direction,
          },
        ];
        this.dataManager(sortOrder);
      } else {
        this.fetch();
      }
    },
  },
};
