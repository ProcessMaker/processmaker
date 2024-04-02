export default {
    methods: {
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
    }
}