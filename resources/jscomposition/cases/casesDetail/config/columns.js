import {
  StatusCell,
  LinkCell,
} from "../../../system/index";

export default {};

// Column for Task
const taskNumberColumn = () => ({
  field: "id",
  header: "Tasks #",
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
  field: "user.fullname",
  header: "Assigned",
  resizable: true,
  width: 200,
  filter: true,
});

const dueDateColumn = () => ({
  field: "due_at",
  header: "Due Date",
  resizable: true,
  width: 200,
  filter: true,
});

// Columns for Requests
const requestNumberColumn = () => ({
  field: "id",
  header: "Request #",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/requests/${row.id}`;
      },
    },
  }),
});

const requestNameColumn = () => ({
  field: "name",
  header: "Request Name",
  resizable: true,
  width: 200,
  cellRenderer: () => ({
    component: LinkCell,
    params: {
      click: (row, column, columns) => {
        window.document.location = `/requests/${row.id}`;
      },
    },
  }),
});

const currentTaskColumn = () => ({
  field: "current_task",
  header: "Current Task",
  resizable: true,
  width: 200,
});

const statusColumn = () => ({
  field: "status",
  header: "Status",
  resizable: true,
  width: 200,
  cellRenderer: () => StatusCell,
});

const startedColumn = () => ({
  field: "created_at",
  header: "started",
  resizable: true,
  width: 200,
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
      requestNumberColumn(),
      requestNameColumn(),
      currentTaskColumn(),
      statusColumn(),
      startedColumn(),
    ],
  };

  return columns[type];
};
