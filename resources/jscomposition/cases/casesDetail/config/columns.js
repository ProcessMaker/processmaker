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
  width: 144,
  filter: true,
  formatter: (row, column, columns) => `#${row.id}`,
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      href: (row) => `/tasks/${row.id}/edit`,
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
      href: (row) => `/tasks/${row.id}/edit`,
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
        window.document.location = `/profile/${row.user?.id}`;
      },
      formatter: (row, column, columns) => {
        return row.user ? row.user.fullname : 'Self Service';
      },
      initials: (row, column, columns) => row.user?.fullname[0],
      src: (row, column, columns) => row.user?.avatar,
    },
  }),
});

const dueDateColumn = () => ({
  field: "due_at",
  header: "Due Date",
  resizable: true,
  width: "auto",
  filter: true,
  formatter: (row, column, columns) => formatDate(row.due_at, "datetime"),
});

// Columns for Requests
const requestIdColumn = () => ({
  field: "id",
  header: "Request ID",
  resizable: true,
  filter: true,
  width: 150,
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      href: (row) => `/requests/${row.id}`,
    },
  }),
});

const processRequestColumn = () => ({
  field: "name",
  header: "Process Name",
  resizable: true,
  width: 200,
  filter: true,
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
      href: (row) => `/tasks/${row.id}/edit`,
      formatterOptions: (option, row, column, columns) => option.element_name,
    },
  }),
});

const statusColumn = () => ({
  field: "status",
  header: "Status",
  filter: true,
  resizable: true,
  width: 140,
  cellRenderer: () => ({
    component: StatusCell,
  }),
});

const startedColumn = () => ({
  field: "initiated_at",
  header: "Started",
  filter: true,
  resizable: true,
  width: "auto",
  formatter: (row, column, columns) => formatDate(row.initiated_at, "datetime"),
});

const completedDateColumn = () => ({
  field: "completed_at",
  header: "Completed",
  resizable: true,
  width: 200,
  formatter: (row, column, columns) => formatDate(row.completed_at, "datetime"),
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
