import { getRequests } from "../../api";

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

export const prepareToGetRequests = async ({
  page, perPage, orderDirection, orderBy, processesIManage,
  allInbox, pmql, filter, advancedFilter, include, statusFilter,
}) => {
  const nPage = page;
  const nPerPage = perPage;
  const nOrderDirection = orderDirection;
  const nOrderBy = orderBy;
  const nPmql = buildPmql(pmql, filter);
  const nFilter = buildPmql(pmql, filter) === pmql ? filter : "";
  const nAdvancedFilter = advancedFilter;

  const nInclude = `process,participants,activeTasks,data${include ? `,${include}` : ""}`;

  const response = await getRequests({
    page: nPage,
    perPage: nPerPage,
    orderDirection: nOrderDirection,
    orderBy: nOrderBy,
    pmql: nPmql,
    filter: nFilter,
    include: nInclude,
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
