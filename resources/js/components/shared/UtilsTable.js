export const ellipsisSortClick = (categoryColumn, context)=>{
    context.fields.forEach(column => {
        if (column.field !== categoryColumn.field) {
        column.direction = "none";
        column.filterApplied = false;
        }
    });

    if (categoryColumn.direction === "asc") {
        categoryColumn.direction = "desc";
    } else if (categoryColumn.direction === "desc") {
        categoryColumn.direction = "none";
        categoryColumn.filterApplied = false;
    } else {
        categoryColumn.direction = "asc";
        categoryColumn.filterApplied = true;
    }

    if (categoryColumn.direction !== "none") {
        const sortOrder = [
        {
            sortField: categoryColumn.sortField || categoryColumn.field,
            direction: categoryColumn.direction,
        },
        ];
        context.dataManager(sortOrder);
    } else {
        context.fetch();
    }
}