import { formatDate } from "../../../utils";

export const formatFilters = (filters) => {
  const response = filters.map((element) => {
    let { value } = element;

    if (!element.operator) {
      return null;
    }

    if (element.operator === "between" || element.operator === "in") {
      value = element.value.map((o) => o.value);
    }

    // Case status is a dropdown filter, using for the API
    if (element.field === "case_status") {
      value = element.value.value;
    }

    return {
      subject: {
        type: "Field",
        value: element.field,
      },
      operator: element.operator,
      value,
    };
  });

  return response.filter((e) => e);
};

// Format filters to convert in badges data
export const formatFilterBadges = (filters, columns) => {
  const response = filters.map((element) => {
    let { value } = element;
    const col = columns.find((c) => c.field === element.field);

    if (!element.operator) {
      return null;
    }

    if (element.operator === "between" || element.operator === "in") {
      value = element.value.map((o) => o.value);
    }

    // Case status is a dropdown filter, using with badges
    if (element.field === "case_status") {
      value = element.value.label;
    }

    // Format datetime value to badges
    if (col.filter.dataType === "datetime") {
      value = value.find ? value.map((o) => formatDate(o)) : formatDate(value);
    }

    return {
      fieldName: col.header,
      field: element.field,
      operator: element.operator,
      value,
    };
  });

  return response.filter((e) => e);
};

export const getDefaultFilters = (id) => {
  const filters = {
    default: [{
      field: "case_status",
      operator: "=",
      value: {
        label: "In progress",
        value: "in_progress",
      },
    }],
    in_progress: [],
    completed: [],
    all: [],
  };

  return filters[id] || filters.default;
};
