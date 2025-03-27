import { t } from "i18next";
import {
  CaseTitleCell,
  TruncatedGroupOptionsCell,
  ParticipantsCell,
  StatusCell,
  LinkCell,
  TruncatedColumn,
} from "../../../system/index";
import { formatDate } from "../../../utils";

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
  width: 144,
  filter: {
    dataType: "string",
    operators: ["=", ">", ">=", "in", "between"],
    resetTable: true,
  },
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      href: (row) => `/cases/${row.case_number}`,
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
      href: (row) => `/cases/${row.case_number}`,
    },
  }),
  filter: {
    dataType: "string",
    operators: ["=", "in", "contains", "regex"],
    resetTable: true,
  },
});

export const processColumn = () => ({
  field: "processes",
  header: "Process",
  resizable: true,
  width: 200,
  filter: {
    resetTable: true,
  },
  cellRenderer: () => ({
    component: TruncatedColumn,
    params: {
      formatterOptions: (option, row, column, columns) => option.name,
    },
  }),
});

export const taskColumn = () => ({
  field: "tasks",
  header: t("Current Task"),
  resizable: true,
  width: 200,
  filter: {
    resetTable: true,
  },
  cellRenderer: () => ({
    component: TruncatedGroupOptionsCell,
    params: {
      href: (option) => `/tasks/${option.id}/edit`,
      formatterOptions: (option, row, column, columns) => option.name,
      formatData: (row, column, columns) => {
        if (row.case_status === "COMPLETED") {
          return [];
        }

        const groupedTasks = row.tasks.reduce((acc, task) => {
          if (task.status !== "ACTIVE") {
            return acc;
          }

          const processId = task.process_id;
          const existGroup = acc.find((group) => group.process_id === processId);

          if (existGroup) {
            existGroup.options.push(task);
          } else {
            acc.push({
              process_id: processId,
              options: [task],
              ...row.processes.find((process) => process.id === processId),
            });
          }

          return acc;
        }, []);

        return groupedTasks;
      },
    },
  }),
});

export const participantsColumn = () => ({
  field: "participants",
  header: "Participants",
  resizable: true,
  width: 200,
  filter: {
    resetTable: true,
  },
  cellRenderer: () => ({
    component: ParticipantsCell,
    params: {
      click: (option, row, column, columns) => {
        window.document.location = `/profile/${option.id}`;
      },
      formatter: (option, row, column, columns) => option.name,
      initials: (option, row, column, columns) => option.name[0],
      src: (option, row, column, columns) => option.avatar,
    },
  }),
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
    dataType: "enum",
    operators: ["="],
    resetTable: true,
    config: {
      options: [{
        label: t("In progress"),
        value: "in_progress",
      },
      {
        label: t("Completed"),
        value: "completed",
      },
      {
        label: t("Error"),
        value: "error",
      },
      {
        label: t("Canceled"),
        value: "canceled",
      }],
    },
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
    resetTable: true,
  },
});

export const completedColumn = () => ({
  field: "completed_at",
  header: "Completed",
  resizable: true,
  width: "auto",
  formatter: (row, column, columns) => formatDate(row.completed_at, "datetime"),
  filter: {
    dataType: "datetime",
    operators: ["between", ">", ">=", "<", "<="],
    resetTable: true,
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
      startedColumn(),
    ],
    completed: [
      caseNumberColumn(),
      caseTitleColumn(),
      processColumn(),
      taskColumn(),
      participantsColumn(),
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
