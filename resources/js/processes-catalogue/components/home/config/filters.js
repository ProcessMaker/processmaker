// Convert value filter from FilterableTable to value for AdvancedFilter
export const buildValue = (operator, value) => {
  switch (operator) {
    case "=":
      return value;
    case "between":
      return value.map((v) => v.value);
    case "in":
      return value.map((v) => v.value);
    case "contains":
      return value;
    default:
      return value;
  }
};

export const buildFilters = ({ defaultColumns, filterData }) => {
  const result = [];

  filterData.forEach((f) => {
    const filter = defaultColumns.find((column) => column.field === f.id);

    if (!filter) {
      return;
    }

    result.push({
      subject: filter.filter_subject,
      operator: f.operator,
      value: buildValue(f.operator, f.value),
    });
  });

  return result;
};
