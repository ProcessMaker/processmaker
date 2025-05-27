import { subjectColumns } from "./requestDefaultColumns";
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
    const filter = subjectColumns.find((column) => column.field === f.id);

    const subject = {
      type: "Field",
    };

    console.log("filter", filter);

    result.push({
      subject: filter.subject || subject,
      operator: f.operator,
      value: buildValue(f.operator, f.value),
    });
  });

  return result;
};
