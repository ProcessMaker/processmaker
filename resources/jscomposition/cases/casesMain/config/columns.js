import AvatarContainer from "../components/AvatarContainer.vue";
import {
  CaseTitleCell,
  TruncatedOptionsCell,
  ParticipantsCell,
  StatusCell,
} from "../../../system/index";
import moment from "moment";

const formatDate = (value, format) => {
  let config = "DD/MM/YYYY hh:mm";
  if (
    typeof ProcessMaker !== "undefined" &&
    ProcessMaker.user &&
    ProcessMaker.user.datetime_format
  ) {
    if (format === "datetime") {
      config = ProcessMaker.user.datetime_format;
    }
    if (format === "date") {
      config = ProcessMaker.user.datetime_format.replace(/[\sHh:msaAzZ]/g, "");
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
 * headerName: String
 * headerFormatter: callback
 * resizable: Boolean
 * visible: Callback
 * formatter: Callback - Build the value in the cell
 * width: Number
 * cellRenderer: Object Vue to custom the cell
 */

// My cases: [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]
// In Progress : [ Case#, Case Title, Process, Task, Participants, Status, Started]
// Completed : [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]
// AllCases : [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]
// AllRequest : [Case#, Case Title, Process, Task, Participants, Status, Started, Completed]

export const caseNumberColumn = () => ({
  field: "case_number",
  headerName: "Case #",
  resizable: true,
  width: 100,
});

export const caseTitleColumn = () => ({
  field: "case_title",
  headerName: "Case Title",
  resizable: true,
  width: 200,
  cellRenderer: () => {
    return CaseTitleCell;
  },
});

export const processColumn = () => ({
  field: "processes",
  headerName: "Process",
  resizable: true,
  width: 200,
  cellRenderer: () => {
    return TruncatedOptionsCell;
  },
});

export const taskColumn = () => ({
  field: "tasks",
  headerName: "Task",
  resizable: true,
  width: 200,
  cellRenderer: () => {
    return TruncatedOptionsCell;
  },
});

export const participantsColumn = () => ({
  field: "participants",
  headerName: "Participants",
  resizable: true,
  width: 200,
  cellRenderer: () => {
    return ParticipantsCell;
  },
});

export const statusColumn = () => ({
  field: "case_status",
  headerName: "Status",
  resizable: true,
  width: 200,
  cellRenderer: () => {
    return StatusCell;
  },
});

export const startedColumn = () => ({
  field: "initiated_at",
  headerName: "Started",
  resizable: true,
  width: 200,
  formatter: (row, column, columns) => {
    return formatDate(row.initiated_at, "datetime");
  },
});

export const completedColumn = () => ({
  field: "completed_at",
  headerName: "Completed",
  resizable: true,
  width: 200,
  formatter: (row, column, columns) => {
    return formatDate(row.completed_at, "datetime");
  },
});

export const getColumns = (type) => {
  const columnsDefinition = {
    "my-cases": [
      caseNumberColumn(),
      caseTitleColumn(),
      processColumn(),
      taskColumn(),
      participantsColumn(),
      statusColumn(),
      startedColumn(),
      completedColumn(),
    ],
    "in-progress": [
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
    "all-cases": [
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

  return columnsDefinition[type] || columnsDefinition.myCases;
};
