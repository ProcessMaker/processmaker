export default {};

// Method to get counters - change with processmaker API
export const getCounters = async () => {
  const url = "http://localhost:3000/appcounters";
  return {
    myCases: 36,
    inProgress: 20,
    completed: 125,
    allCases: 145,
    allRequests: 777,
  };
};

// Method to get data case list - change with processmaker API
export const getData = async () => {
  let objects_list = [];

  for (let i = 0; i <= 31; i++) {
    const obj = {
      caseNumber: `${i}`,
      caseTitle: `Case Title ${i}`,
      process: `Process ${i}`,
      task: `Task ${i}`,
      participants: `Avatar ${i}`,
      status: `badge ${i}`,
      started: `21/21/${i}`,
      completed: `22/22/${i}`,
    };

    objects_list.push(obj);
  }

  return objects_list;
};
