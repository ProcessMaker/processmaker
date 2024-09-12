import BadgeContainer from "../components/BadgeContainer.vue";
import AvatarContainer from "../components/AvatarContainer.vue";

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
  field: "caseNumber",
  headerName: "Case #",
  resizable: true,
  width: 200,
});

export const caseTitleColumn = () => ({
  field: "caseTitle",
  headerName: "Case Title",
  resizable: true,
  width: 200,
});

export const processColumn = () => ({
  field: "process",
  headerName: "Process",
  resizable: true,
  width: 200,
});

export const taskColumn = () => ({
  field: "task",
  headerName: "Task",
  resizable: true,
  width: 200,
});

export const participantsColumn = () => ({
  field: "participants",
  headerName: "Participants",
  resizable: true,
  width: 200,
  cellRenderer: () => {
    return AvatarContainer;
  },
});

export const statusColumn = () => ({
  field: "status",
  headerName: "Status",
  resizable: true,
  width: 200,
  cellRenderer: () => {
    return BadgeContainer;
  },
});

export const startedColumn = () => ({
  field: "started",
  headerName: "Started",
  resizable: true,
  width: 200,
});

export const completedColumn = () => ({
  field: "completed",
  headerName: "Completed",
  resizable: true,
  width: 200,
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
