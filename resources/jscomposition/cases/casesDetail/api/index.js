export default {};

const getData = async () => {
  const objectsList = [];

  for (let i = 0; i <= 31; i += 1) {
    const obj = {
      id: `${i}`,
      case_title: `Case Title ${i}`,
      process_name: `Process ${i}`,
      assigned: `Avatar ${i}`,
      current_task: `Task ${i}`,
      status: `badge ${i}`,
      started: `21/21/${i}`,
      due_date: `21/21/${i}`,
    };

    objectsList.push(obj);
  }

  return objectsList;
};

export { getData };
