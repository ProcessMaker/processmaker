import { formatDate } from "../../../utils";

export const formatFilters = (filters) => {
  const response = filters.map((element) => {
    let { value } = element;
    let label = "";

    if (!element.operator) {
      return null;
    }

    if (element.operator === "between" || element.operator === "in") {
      value = element.value.map((o) => o.value);
    }

    // Case status is a dropdown filter, using for the API
    if (element.field === "case_status") {
      value = element.value.value;
      label = element.value.label;
    }

    return {
      subject: {
        type: "Field",
        value: element.field,
      },
      operator: element.operator,
      value: value || "",
      label,
      _column_field: element.field,
    };
  });

  return response.filter((e) => e);
};

export const getFilterOrder = (filters) => {
  let response = {};
  filters.forEach((element) => {
    if (element.sortable) {
      response = {
        by: element.field,
        direction: element.sortable,
      };
    }
  });
  return response;
};

export const formattedFilter = (filters) => {
  const response = formatFilters(filters);
  const order = getFilterOrder(filters);
  return {
    filters: [...response],
    order,
  };
};

export const formatFilterSaved = (filters) => {
  if (!filters) {
    return [];
  }
  const response = filters.map((element) => {
    let value = "";
    if (element.subject.value === "case_status") {
      value = {
        value: element.value,
        label: element.label,
      };
    } else {
      value = element.value;
    }
    return {
      field: element.subject.value,
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
      value = value && value.find ? value.map((o) => formatDate(o)) : formatDate(value);
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
