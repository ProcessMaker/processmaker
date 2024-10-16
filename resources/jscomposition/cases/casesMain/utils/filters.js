import { formatDate } from "../../../utils";

export const formatFilters = (filters) => {
  const response = filters.map((e) => {
    let { value } = e;

    if (!e.operator) {
      return null;
    }

    if (e.operator === "between" || e.operator === "in") {
      value = e.value.map((o) => o.value);
    }

    return {
      subject: {
        type: "Field",
        value: e.field,
      },
      operator: e.operator,
      value,
    };
  });

  return response.filter((e) => e);
};

// Format filters to convert in badges data
export const formatFilterBadges = (filters, columns) => {
  const response = filters.map((e) => {
    let { value } = e;
    const col = columns.find((c) => c.field === e.field);

    if (!e.operator) {
      return null;
    }

    if (e.operator === "between" || e.operator === "in") {
      value = e.value.map((o) => o.value);
    }

    // Format datetime value to badges
    if (col.filter.dataType === "datetime") {
      value = value.find ? value.map((o) => formatDate(o)) : formatDate(value);
    }

    return {
      fieldName: col.header,
      field: e.field,
      operator: e.operator,
      value,
    };
  });

  return response.filter((e) => e);
};
