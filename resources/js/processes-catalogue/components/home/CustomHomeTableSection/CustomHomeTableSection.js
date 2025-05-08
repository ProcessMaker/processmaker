import { getTasks } from "../../api";

export const buildPmql = (pmql, filter) => {
  let pmqlBuilded = null;

  if (pmql !== undefined) {
    pmqlBuilded = pmql;
  }

  if (filter && filter.length && filter.isPMQL()) {
    pmqlBuilded = `(${pmql}) and (${filter})`;
  }

  return pmqlBuilded;
};

export const prepareToGetTasks = async ({
  page, perPage, orderDirection, orderBy, processesIManage,
  allInbox, pmql, filter, advancedFilter, include, statusFilter,
}) => {
  const nPage = page;
  const nPerPage = perPage;
  const nOrderDirection = orderDirection;
  const nOrderBy = orderBy;
  const nNonSystem = true;
  const nProcessesIManage = processesIManage;
  const nAllInbox = allInbox;
  const nPmql = buildPmql(pmql, filter);
  const nFilter = buildPmql(pmql, filter) === pmql ? filter : "";
  const nAdvancedFilter = advancedFilter;

  const nInclude = `process,processRequest,processRequest.user,user,data${include ? `,${include}` : ""}`;

  const response = await getTasks({
    page: nPage,
    perPage: nPerPage,
    orderDirection: nOrderDirection,
    orderBy: nOrderBy,
    nonSystem: nNonSystem,
    processesIManage: nProcessesIManage,
    allInbox: nAllInbox,
    pmql: nPmql,
    filter: nFilter,
    include: nInclude,
    statusFilter,
    advancedFilter: nAdvancedFilter,
  });
  return response;
};

export const buildColumns = (defaultColumns) => {
  const columns = [];
  defaultColumns.forEach((column) => {
    columns.push(column);
  });
  return columns;
};
