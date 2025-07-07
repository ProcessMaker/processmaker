/**
 * Only mao the subjects for filters
 */
export const subjectColumns = [
  {
    field: "id",
    subject: {
      type: "Field",
      value: "id",
    },
  },
  {
    field: "case_number",
    subject: {
      type: "Field",
      value: "case_number",
    },
  },
  {
    field: "case_title",
    subject: {
      type: "Field",
      value: "case_title",
    },
  },
  {
    field: "name",
    subject: {
      type: "Field",
      value: "name",
    },
  },
  {
    field: "stage",
    subject: {
      type: "Field",
      value: "stage",
    },
  },
  {
    field: "progress",
    subject: {
      type: "Field",
      value: "progress",
    },
  },
  {
    field: "participants",
    subject: { type: "ParticipantsFullName" },
  },
  {
    field: "status",
    subject: { type: "Status" },
  },
  {
    field: "initiated_at",
    subject: {
      type: "Field",
      value: "initiated_at",
    },
  },
  {
    field: "completed_at",
    subject: {
      type: "Field",
      value: "completed_at",
    },
  },
];

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

    result.push({
      subject: filter.subject || subject,
      operator: f.operator,
      value: buildValue(f.operator, f.value),
    });
  });

  return result;
};
