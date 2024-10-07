import moment from "moment";

import {
  CaseTitleCell,
  TruncatedOptionsCell,
  ParticipantsCell,
  StatusCell,
  LinkCell,
} from "../../../system/index";

const formatDate = (value, format) => {
  let config = "DD/MM/YYYY hh:mm";
  if (
    typeof ProcessMaker !== "undefined"
    && ProcessMaker.user
    && ProcessMaker.user.datetime_format
  ) {
    if (format === "datetime") {
      config = ProcessMaker.user.datetime_format;
    }
    if (format === "date") {
      config = ProcessMaker.user.datetime_format.replace(
        /[\sHh:msaAzZ]/g,
        "",
      );
    }
  }
  if (value) {
    if (moment(value).isValid()) {
      return moment(value).format(config);
    }
    return value;
  }
  return "n/a";
};

export default {};
/**
 * Example Column
 * field: String
 * header: String
 * headerFormatter: callback
 * resizable: Boolean
 * visible: Callback
 * formatter: Callback - Build the value in the cell
 * width: Number
 * cellRenderer: Object Vue to custom the cell
 * filter: This attribute is optional
 */

// My cases: [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]
// In Progress : [ Case#, Case Title, Process, Task, Participants, Status, Started]
// Completed : [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]
// AllCases : [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]
// AllRequest : [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]

export const caseNumberColumn = () => ({
  field: "case_number",
  header: "Case #",
  resizable: true,
  width: 100,
  filter: {
    dataType: "string",
    operators: ["=", ">", ">=", "in", "between"],
  },
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/cases/${row.case_number}`;
      },
    },
  }),
});

export const caseTitleColumn = () => ({
  field: "case_title",
  header: "Case Title",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: CaseTitleCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/cases/${row.case_number}`;
      },
    },
  }),
  filter: {
    dataType: "string",
    operators: ["=", ">", ">=", "in", "between"],
  },
});

export const processColumn = () => ({
  field: "processes",
  header: "Process",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: TruncatedOptionsCell,
    params: {
      click: (option, row, column, columns) => {
        window.document.location = `/tasks/${option.id}/edit`;
      },
      formatterOptions: (option, row, column, columns) => option.name,
    },
  }),
  filter: {
    dataType: "string",
    operators: ["="],
  },
});

export const taskColumn = () => ({
  field: "tasks",
  header: "Task",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: TruncatedOptionsCell,
    params: {
      click: (option, row, column, columns) => {
        window.document.location = `/tasks/${option.id}/edit`;
      },
      formatterOptions: (option, row, column, columns) => option.name,
    },
  }),
  filter: {
    dataType: "string",
    operators: ["="],
  },
});

export const participantsColumn = () => ({
  field: "participants",
  header: "Participants",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: ParticipantsCell,
    params: {
      click: (option, row, column, columns) => {
        window.document.location = `/profile/${option.id}`;
      },
    },
  }),
  filter: {
    dataType: "string",
    operators: ["="],
  },
});

export const statusColumn = () => ({
  field: "case_status",
  header: "Status",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: StatusCell,
  }),
  filter: {
    dataType: "string",
    operators: ["="],
  },
});

export const startedColumn = () => ({
  field: "initiated_at",
  header: "Started",
  resizable: true,
  width: 200,
  formatter: (row, column, columns) => formatDate(row.initiated_at, "datetime"),
  filter: {
    dataType: "datetime",
    operators: ["between", ">", ">=", "<", "<="],
  },
});

export const completedColumn = () => ({
  field: "completed_at",
  header: "Completed",
  resizable: true,
  width: 200,
  formatter: (row, column, columns) => formatDate(row.completed_at, "datetime"),
  filter: {
    dataType: "datetime",
    operators: ["between", ">", ">=", "<", "<="],
  },
});

export const getColumns = (type) => {
  const columnsDefinition = {
    default: [
      caseNumberColumn(),
      caseTitleColumn(),
      processColumn(),
      taskColumn(),
      participantsColumn(),
      statusColumn(),
      startedColumn(),
      completedColumn(),
    ],
    in_progress: [
      caseNumberColumn(),
      caseTitleColumn(),
      processColumn(),
      taskColumn(),
      participantsColumn(),
      statusColumn(),
      startedColumn(),
    ],
    completed: [
      caseNumberColumn(),
      caseTitleColumn(),
      processColumn(),
      taskColumn(),
      participantsColumn(),
      statusColumn(),
      startedColumn(),
      completedColumn(),
    ],
    all: [
      caseNumberColumn(),
      caseTitleColumn(),
      processColumn(),
      taskColumn(),
      participantsColumn(),
      statusColumn(),
      startedColumn(),
      completedColumn(),
    ],
  };

  return columnsDefinition[type] || columnsDefinition.default;
};
