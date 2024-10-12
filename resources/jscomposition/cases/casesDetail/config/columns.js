import {
  LinkCell,
  StatusCell,
  TruncatedOptionsCell,
  CollapseFormCell,
  ParticipantCell,
} from "../../../system/index";
import { formatDate } from "../../../utils";

export default {};

// Column for Task
const taskNumberColumn = () => ({
  field: "id",
  header: "Tasks #",
  resizable: true,
  width: 100,
  filter: true,
  formatter: (row, column, columns) => `#${row.id}`,
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/tasks/${row.id}/edit`;
      },
    },
  }),
});

const taskNameColumn = () => ({
  field: "element_name",
  header: "Task Name",
  resizable: true,
  width: 200,
  filter: true,
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/tasks/${row.id}/edit`;
      },
    },
  }),
});

const processNameColumn = () => ({
  field: "process.name",
  header: "Process",
  resizable: true,
  width: 200,
});

const assignedColumn = () => ({
  field: "user",
  header: "Assigned",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: ParticipantCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/profile/${row.user.id}`;
      },
      formatter: (row, column, columns) => row.user.fullname,
      initials: (row, column, columns) => row.user.fullname[0],
    },
  }),
});

const dueDateColumn = () => ({
  field: "due_at",
  header: "Due Date",
  resizable: true,
  width: 200,
  filter: true,
  formatter: (row, column, columns) => formatDate(row.due_at),
});

// Columns for Requests
const requestIdColumn = () => ({
  field: "id",
  header: "Request ID",
  resizable: true,
  filter: { type: "sortable" },
  width: 150,
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/requests/${row.id}`;
      },
    },
  }),
});

const processRequestColumn = () => ({
  field: "name",
  header: "Process Name",
  resizable: true,
  width: 200,
  filter: { type: "sortable" },
  cellRenderer: () => ({
    component: LinkCell,
    params: {},
  }),
});

const taskColumn = () => ({
  field: "active_tasks",
  header: "Task",
  resizable: true,
  width: 140,
  formatter: (row, column, columns) => (row.active_tasks.length ? row.active_tasks[0].element_name : ""),
  cellRenderer: () => ({
    component: TruncatedOptionsCell,
    params: {
      click: (option, row, column, columns) => {
        window.document.location = `/tasks/${option.id}/edit`;
      },
      formatterOptions: (option, row, column, columns) => option.element_name,
    },
  }),
});

const statusColumn = () => ({
  field: "status",
  header: "Status",
  filter: { type: "sortable" },
  resizable: true,
  width: 140,
  cellRenderer: () => ({
    component: StatusCell,
  }),
});

const startedColumn = () => ({
  field: "initiated_at",
  header: "Started",
  filter: { type: "sortable" },
  resizable: true,
  width: 200,
  formatter: (row, column, columns) => formatDate(row.initiated_at),
});

const completedDateColumn = () => ({
  field: "completed_at",
  header: "Completed",
  resizable: true,
  width: 200,
  formatter: (row, column, columns) => formatDate(row.completed_at),
});

const actionColumn = () => ({
  field: "",
  header: "",
  resizable: false,
  width: 50,
  cellRenderer: () => ({
    component: CollapseFormCell,
    params: {
      show: (row, column, columns) => true,
    },
  }),
  params: {},
});

export const getColumns = (type) => {
  const columns = {
    tasks: [
      taskNumberColumn(),
      taskNameColumn(),
      processNameColumn(),
      assignedColumn(),
      dueDateColumn(),
    ],
    requests: [
      requestIdColumn(),
      processRequestColumn(),
      taskColumn(),
      statusColumn(),
      startedColumn(),
    ],
    completed_forms: [
      actionColumn(),
      taskNumberColumn(),
      taskNameColumn(),
      processNameColumn(),
      assignedColumn(),
      completedDateColumn(),
      dueDateColumn(),
    ],
  };

  return columns[type];
};
