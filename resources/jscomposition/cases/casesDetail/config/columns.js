import {
  LinkCell,
  StatusCell,
  TruncatedOptionsCell,
  CollapseFormCell,
} from "../../../system/index";

export default {};

// Column for Task
const taskNumberColumn = () => ({
  field: "case_number",
  header: "Tasks #",
  resizable: true,
  width: 200,
  filter: true,
  formatter:(row, column, columns)=>{
    return row.element_name;
  },
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
const requestIdColumn = () => ({
  field: "id",
  header: "Request ID",
  resizable: true,
  filter: { type: "sortable" },
  width: 80,
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
    params: {
      click: (row, column, columns) => {
        window.document.location = `/requests/${row.id}`;
      },
    },
  }),
});

const taskColumn = () => ({
  field: "active_tasks",
  header: "Task",
  resizable: true,
  width: 140,
  formatter:(row, column, columns)=>{
    return row.active_tasks.length? row.active_tasks[0].element_name : "";
  },
  cellRenderer: () => ({
    component: TruncatedOptionsCell,
    params: {
      click: (option, row, column, columns) => {
        window.document.location = `/tasks/${option.id}/edit`;
      },
      formatterOptions:(option, row, column, columns)=>{
        return option.element_name;
      }
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
});

const completedDateColumn = () => ({
  field: "completed_date",
  header: "Completed Date",
  resizable: true,
  width: 200,
});

const actionColumn = () => ({
  field: "",
  header: "",
  resizable: false,
  width: 50,
  cellRenderer: () => CollapseFormCell,
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
