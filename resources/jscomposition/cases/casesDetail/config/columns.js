import {
  ParticipantsCell,
  StatusCell,
} from "../../../system/index";


export default {};

// Column for Task
const taskNumberColumn = () => ({
  field: "id",
  header: "Tasks #",
  resizable: true,
  width: 200,
});

const taskNameColumn = () => ({
  field: "case_title",
  header: "Task Name",
  resizable: true,
  width: 200,
});

const processNameColumn = () => ({
  field: "process_name",
  header: "Process",
  resizable: true,
  width: 200,
});

const assignedColumn = () => ({
  field: "assigned",
  header: "Assigned",
  resizable: true,
  width: 200
});

const dueDateColumn = () => ({
  field: "due_date",
  header: "Due Date",
  resizable: true,
  width: 200,
});

// Columns for Requests
const requestNumberColumn = () => ({
  field: "id",
  header: "Request #",
  resizable: true,
  width: 200,
});

const requestNameColumn = () => ({
  field: "case_title",
  header: "Request Name",
  resizable: true,
  width: 200,
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
  field: "started_date",
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
