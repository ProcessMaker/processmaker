import { t } from "i18next";
import { get } from "lodash";
import {
  LinkCell, TitleCell, FlagCell, StatusCell, TruncatedOptionsCell, ParticipantsCell, ProgressBarCell,
} from "../../../../../jscomposition/system/index";
import { formatDate } from "../../../../../jscomposition/utils";
// Columns in the table:

export const requestNumberColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  formatter: (row, column, columns) => `# ${row.id}`,
  filter: {
    dataType: "string",
    operators: ["=", ">", ">=", "in", "between"],
    resetTable: true,
  },
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      href: (row) => `/requests/${get(row, field)}`,
    },
  }),
});

export const caseNumberColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  formatter: (row, column, columns) => `# ${row.id}`,
  filter: {
    dataType: "string",
    operators: ["=", ">", ">=", "in", "between"],
    resetTable: true,
  },
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      href: (row) => `/cases/${get(row, field)}`,
    },
  }),
});

export const caseTitleColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  cellRenderer: () => ({
    component: TitleCell,
    params: {
      href: (row) => `/cases/${row.case_number}`,
    },
  }),
  filter: {
    dataType: "string",
    operators: ["=", "in", "contains", "regex"],
    resetTable: true,
  },
});

export const textColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  filter: {
    dataType: "string",
    operators: ["=", "in", "contains", "regex"],
    resetTable: true,
  },
});

export const flagColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header: " ",
  resizable,
  width: 50,
  cellRenderer: () => ({
    component: FlagCell,
    params: {
      active: (row) => get(row, field),
      click: (active, row, column, columns) => {
        console.log(active, row, column, columns);
      },
    },
  }),
});

export const progressColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width: 200,
  cellRenderer: () => ({
    component: ProgressBarCell,
    params: {
      data: (row, column, columns) => get(row, field),
      color: "green",
    },
  }),
});

export const taskColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  cellRenderer: () => ({
    component: TruncatedOptionsCell,
    params: {
      href: (option) => `/tasks/${option.id}/edit`,
      formatterOptions: (option, row, column, columns) => option.element_name,
      filterData: (row, column, columns) => row.active_tasks,
    },
  }),
});

export const participantsColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  filter: {
    dataType: "string",
    operators: ["=", "in", "contains", "regex"],
    resetTable: true,
  },
  cellRenderer: () => ({
    component: ParticipantsCell,
    params: {
      click: (option, row, column, columns) => {
        window.document.location = `/profile/${option.id}`;
      },
      formatter: (option, row, column, columns) => option.username,
      initials: (option, row, column, columns) => option.username[0],
      src: (option, row, column, columns) => option.avatar,
    },
  }),
});

export const statusColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  cellRenderer: () => ({
    component: StatusCell,
  }),
  filter: {
    dataType: "enum",
    operators: ["="],
    resetTable: true,
    config: {
      options: [{
        label: t("In progress"),
        value: "In progress",
      },
      {
        label: t("Completed"),
        value: "Completed",
      },
      {
        label: t("Error"),
        value: "Error",
      },
      {
        label: t("Overdue"),
        value: "overdue",
      },
      {
        label: t("Canceled"),
        value: "Canceled",
      }],
    },
  },
});

export const dateColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
  formatter: (row, column, columns) => formatDate(row[field], "datetime"),
  filter: {
    dataType: "datetime",
    operators: ["between", ">", ">=", "<", "<="],
    resetTable: true,
  },
});

export const defaultColumn = ({
  id, field, header, resizable, width,
}) => ({
  id,
  field,
  header,
  resizable,
  width,
});

export const getColumns = (type) => {
  const columnsDefinition = {
    default: [
      requestNumberColumn(),
      caseNumberColumn(),
      caseTitleColumn(),
      flagColumn(),
      taskColumn(),
      statusColumn(),
      dateColumn(),
    ],
  };

  return columnsDefinition[type] || columnsDefinition.default;
};

/// /////////////////////////////////////////////////////////
// CONVERT DEFAULT COLUMNS FROM BE TO OUR FORMAT

// const convertColumn = {
//     id: 'string', // id of the column
//     field: 'string', // variable to show  ex: processRequest.case_number
//     header: 'string', // label of the column
//     resizable: true,
//     width: 144
// };

/// /////////////////////////////////////////////////////////

export const buildColumns = (defaultColumns) => {
  const columns = [];

  defaultColumns.forEach((column) => {
  // Convert column format from type 'a' to type 'b'
    const convertedColumn = {
      id: column.field,
      field: column.field,
      header: column.label,
      resizable: true,
      width: column.width || 144,
    };

    let newColumn = null;

    switch (column.field) {
      case "id":
        newColumn = requestNumberColumn(convertedColumn);
        break;
      case "case_number":
        newColumn = caseNumberColumn(convertedColumn);
        break;
      case "case_title":
        newColumn = caseTitleColumn(convertedColumn);
        break;
      case "name":
        newColumn = textColumn(convertedColumn);
        break;
      case "active_tasks":
        newColumn = taskColumn(convertedColumn);
        break;
      case "participants":
        newColumn = participantsColumn(convertedColumn);
        break;
      case "status":
        newColumn = statusColumn(convertedColumn);
        break;
      case "stage":
        newColumn = defaultColumn(convertedColumn);
        break;
      case "progress":
        newColumn = progressColumn(convertedColumn);
        break;
      case "initiated_at":
        newColumn = dateColumn(convertedColumn);
        break;
      case "completed_at":
        newColumn = dateColumn(convertedColumn);
        break;
      default:
        newColumn = defaultColumn(convertedColumn);
    }

    columns.push(newColumn);
  });

  return columns;
};
