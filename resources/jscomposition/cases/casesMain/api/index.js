import { api } from "../variables";

export default {};

// Method to get data case list - change with processmaker API
export const getData = async () => {
  const objects_list = [];

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

export const allCasesData = () => ({
  data: [
    {
      case_number: 0,
      user_id: 3,
      case_title: "aut enim natus",
      case_title_formatted: "ratione repellat <b>rerum</b>",
      case_status: "COMPLETED",
      processes: [
        { id: 5, name: "eligendi ut" },
        { id: 29869, name: "dolorem qui" },
        { id: 3, name: "accusantium consectetur" },
      ],
      requests: [
        {
          id: 92570,
          name: "delectus voluptatem",
          parent_request: 24,
        },
        { id: 8846, name: "est accusamus culpa", parent_request: 2 },
      ],
      request_tokens: null,
      tasks: [
        { id: "123", name: "libero tenetur quos quibusdam" },
        { id: "node_2329", name: "modi voluptas quo" },
        { id: "node_4561", name: "asperiores tenetur" },
      ],
      participants: [{ id: 25, name: "Dr. Madie Predovic PhD" }],
      initiated_at: "1997-08-01T19:59:17.000000Z",
      completed_at: "2015-11-10T21:25:11.000000Z",
    },
    {
      case_number: 1,
      user_id: 3,
      case_title: "et tempora omnis",
      case_title_formatted: "odio reprehenderit eum",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 39969254, name: "aut itaque" },
        { id: 316545, name: "voluptatem optio" },
        { id: 540006393, name: "et dolor" },
      ],
      requests: [
        { id: 689, name: "voluptas aut", parent_request: 185512574 },
        {
          id: 3262060,
          name: "adipisci est qui",
          parent_request: 93397820,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_8166", name: "asperiores qui qui molestias" },
        { id: "node_4102", name: "non repudiandae aut" },
        { id: "node_4351", name: "ducimus facilis" },
      ],
      participants: [
        { id: 122025, name: "Jaylin Heaney" },
        { id: 6, name: "Jessika Heller" },
        { id: 748506844, name: "Jillian Gibson" },
      ],
      initiated_at: "1980-12-28T08:22:58.000000Z",
      completed_at: "2012-07-05T12:27:04.000000Z",
    },
    {
      case_number: 2,
      user_id: 3,
      case_title: "qui cum amet",
      case_title_formatted: "ad ab modi",
      case_status: "COMPLETED",
      processes: [
        { id: 6624, name: "velit voluptatibus" },
        { id: 34, name: "consequatur quis" },
        { id: 527764, name: "sint dolores" },
      ],
      requests: [
        { id: 57, name: "impedit ducimus", parent_request: 476 },
        { id: 4, name: "qui dolorum non", parent_request: 688 },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_1050", name: "maiores vel iste a" },
        { id: "node_7764", name: "vel nesciunt ratione" },
        { id: "node_4394", name: "molestiae qui" },
      ],
      participants: [
        { id: 8, name: "Kristofer Crist" },
        { id: 163202910, name: "Leonard Bergnaum DDS" },
        { id: 444, name: "Mr. Arvid Schiller MD" },
      ],
      initiated_at: "1983-05-19T16:24:24.000000Z",
      completed_at: "2019-09-07T09:29:56.000000Z",
    },
    {
      case_number: 3,
      user_id: 3,
      case_title: "voluptatem quidem quia",
      case_title_formatted: "qui officiis sapiente",
      case_status: "COMPLETED",
      processes: [
        { id: 92, name: "repellendus voluptatibus" },
        { id: 880611150, name: "architecto est" },
        { id: 9284, name: "eligendi veniam" },
      ],
      requests: [
        { id: 3, name: "et aliquid", parent_request: 34377361 },
        {
          id: 400403,
          name: "consequatur vel magni",
          parent_request: 81981614,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_1259", name: "et temporibus totam quia" },
        { id: "node_6096", name: "perferendis animi sapiente" },
        { id: "node_3455", name: "ut occaecati" },
      ],
      participants: [
        { id: 448166, name: "Eldon Cartwright DVM" },
        { id: 62433751, name: "Miss Ofelia Grimes" },
        { id: 96612, name: "Dr. Wilburn Treutel" },
      ],
      initiated_at: "2006-05-21T07:20:51.000000Z",
      completed_at: "1982-03-29T07:36:25.000000Z",
    },
    {
      case_number: 4,
      user_id: 1,
      case_title: "sit ut sit",
      case_title_formatted: "voluptatem deleniti commodi",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 767, name: "cupiditate in" },
        { id: 38716, name: "recusandae sequi" },
        { id: 5101818, name: "ut atque" },
      ],
      requests: [
        { id: 845, name: "est voluptates", parent_request: 991 },
        {
          id: 6922,
          name: "repellat deserunt vitae",
          parent_request: 7,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_9443", name: "et dignissimos quibusdam esse" },
        { id: "node_3803", name: "fuga voluptatem ratione" },
        { id: "node_6273", name: "non omnis" },
      ],
      participants: [
        { id: 2, name: "Allene Purdy" },
        { id: 13471022, name: "Rhiannon Beer DDS" },
        { id: 493335, name: "Emma Lemke PhD" },
      ],
      initiated_at: "1989-01-02T20:52:49.000000Z",
      completed_at: "1990-07-04T19:11:55.000000Z",
    },
    {
      case_number: 5,
      user_id: 1,
      case_title: "est est ad",
      case_title_formatted: "dicta vel molestiae",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 69224450, name: "vero ea" },
        { id: 2477, name: "excepturi voluptatem" },
        { id: 11857, name: "aut occaecati" },
      ],
      requests: [
        {
          id: 95,
          name: "molestias voluptatem",
          parent_request: 6682658,
        },
        { id: 9706673, name: "et quasi ipsum", parent_request: 2 },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_1023", name: "quia a molestiae labore" },
        { id: "node_8478", name: "assumenda omnis quis" },
        { id: "node_1863", name: "repellendus saepe" },
      ],
      participants: [
        { id: 5762571, name: "Prof. Chad Ledner" },
        { id: 9550, name: "Leta Wunsch Jr." },
        { id: 6589672, name: "Mr. Raul Turcotte" },
      ],
      initiated_at: "2018-07-11T00:44:04.000000Z",
      completed_at: "1977-04-21T20:33:50.000000Z",
    },
    {
      case_number: 6,
      user_id: 1,
      case_title: "ullam error doloribus",
      case_title_formatted: "officiis molestiae ut",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 5181637, name: "delectus dolores" },
        { id: 58250809, name: "fuga beatae" },
        { id: 93813951, name: "atque neque" },
      ],
      requests: [
        {
          id: 6694520,
          name: "nihil aperiam",
          parent_request: 525456,
        },
        {
          id: 9067565,
          name: "beatae voluptatem dolorem",
          parent_request: 0,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_8227", name: "totam rerum aut ipsum" },
        { id: "node_5132", name: "culpa nisi deleniti" },
        { id: "node_7065", name: "exercitationem minus" },
      ],
      participants: [
        { id: 37343205, name: "Abbey Fay" },
        { id: 9459, name: "Dr. Ricardo Bernier" },
        { id: 790987, name: "Baylee Brekke" },
      ],
      initiated_at: "2022-12-11T20:48:55.000000Z",
      completed_at: "2021-11-07T18:15:58.000000Z",
    },
    {
      case_number: 7,
      user_id: 3,
      case_title: "eaque amet repellendus",
      case_title_formatted: "et debitis sit",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 328969, name: "facere sit" },
        { id: 30, name: "aut omnis" },
        { id: 261429681, name: "rerum est" },
      ],
      requests: [
        { id: 5157906, name: "voluptate ratione", parent_request: 0 },
        {
          id: 60923,
          name: "voluptatem eius ipsa",
          parent_request: 27,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_8511", name: "cumque exercitationem quia sit" },
        { id: "node_4341", name: "et qui aliquid" },
        { id: "node_8437", name: "rerum exercitationem" },
      ],
      participants: [
        { id: 7556, name: "Bonnie Altenwerth" },
        { id: 442, name: "Prof. Nathanael Vandervort" },
        { id: 23533498, name: "Kirk Pfeffer" },
      ],
      initiated_at: "1974-10-13T02:35:47.000000Z",
      completed_at: "1976-05-06T00:54:14.000000Z",
    },
    {
      case_number: 8,
      user_id: 3,
      case_title: "reiciendis optio dicta",
      case_title_formatted: "voluptas omnis culpa",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 44327628, name: "quod illum" },
        { id: 5080232, name: "in omnis" },
        { id: 54, name: "veritatis qui" },
      ],
      requests: [
        {
          id: 4444,
          name: "corrupti adipisci",
          parent_request: 2310454,
        },
        {
          id: 562211360,
          name: "voluptatibus id omnis",
          parent_request: 4,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_8141", name: "fugiat tenetur nihil pariatur" },
        { id: "node_1343", name: "omnis labore illo" },
        { id: "node_0356", name: "aut aspernatur" },
      ],
      participants: [
        { id: 2, name: "Luella Gislason" },
        { id: 47785406, name: "Miss Janae Turner" },
        { id: 96768614, name: "Amya Larson" },
      ],
      initiated_at: "1978-01-13T20:53:00.000000Z",
      completed_at: "2003-05-09T01:51:49.000000Z",
    },
    {
      case_number: 9,
      user_id: 1,
      case_title: "sint eius corporis",
      case_title_formatted: "eaque ea quas",
      case_status: "COMPLETED",
      processes: [
        { id: 219515988, name: "consequatur maiores" },
        { id: 174836, name: "soluta sed" },
        { id: 9, name: "hic architecto" },
      ],
      requests: [
        { id: 3084, name: "tempore incidunt", parent_request: 30470 },
        {
          id: 2,
          name: "neque aut suscipit",
          parent_request: 81171529,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_7893", name: "quos ipsam odit quia" },
        { id: "node_0942", name: "et voluptatem perferendis" },
        { id: "node_3020", name: "voluptas recusandae" },
      ],
      participants: [
        { id: 8, name: "Kira Buckridge" },
        { id: 34, name: "Briana Rath" },
        { id: 3526, name: "Mr. Franco Veum" },
      ],
      initiated_at: "1988-02-11T06:46:46.000000Z",
      completed_at: "1997-11-09T18:06:35.000000Z",
    },
    {
      case_number: 11,
      user_id: 3,
      case_title: "facere accusantium sequi",
      case_title_formatted: "inventore et sequi",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 94422, name: "sapiente culpa" },
        { id: 4, name: "vero dolorum" },
        { id: 294713669, name: "sit dolor" },
      ],
      requests: [
        {
          id: 898,
          name: "voluptatum perferendis",
          parent_request: 116178,
        },
        {
          id: 96313178,
          name: "dolor quis ad",
          parent_request: 480366,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_3665", name: "eligendi explicabo suscipit sed" },
        { id: "node_7456", name: "eaque pariatur consectetur" },
        { id: "node_8114", name: "dignissimos occaecati" },
      ],
      participants: [
        { id: 52560, name: "Turner Schuppe" },
        { id: 16577119, name: "Chasity Reinger" },
        { id: 116, name: "Victor Padberg" },
      ],
      initiated_at: "1995-05-26T12:28:06.000000Z",
      completed_at: "1995-12-13T11:27:23.000000Z",
    },
    {
      case_number: 12,
      user_id: 1,
      case_title: "quasi perspiciatis ut",
      case_title_formatted: "perferendis non ut",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 284983263, name: "dolorem soluta" },
        { id: 95252498, name: "quos aut" },
        { id: 1115, name: "sed saepe" },
      ],
      requests: [
        { id: 17469, name: "tenetur temporibus", parent_request: 3 },
        {
          id: 94439,
          name: "odio accusantium sed",
          parent_request: 189566,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_6891", name: "unde ratione ab quia" },
        { id: "node_2660", name: "tenetur odio sed" },
        { id: "node_5814", name: "ut unde" },
      ],
      participants: [
        { id: 44, name: "Kathlyn Johns IV" },
        { id: 447100, name: "Mr. Jamie Yundt" },
        { id: 6992588, name: "Cade McCullough" },
      ],
      initiated_at: "1994-05-27T02:03:26.000000Z",
      completed_at: "2021-01-29T20:34:17.000000Z",
    },
    {
      case_number: 13,
      user_id: 1,
      case_title: "recusandae quas provident",
      case_title_formatted: "placeat veniam fugiat",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 16, name: "ad ratione" },
        { id: 605, name: "molestiae libero" },
        { id: 57, name: "quia aspernatur" },
      ],
      requests: [
        { id: 4536, name: "sit quia", parent_request: 78 },
        { id: 7982002, name: "ea ut itaque", parent_request: 7 },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_1228", name: "error vero exercitationem in" },
        { id: "node_1423", name: "et quia voluptas" },
        { id: "node_8448", name: "consequatur ipsa" },
      ],
      participants: [
        { id: 629, name: "Mrs. Vivianne Kling Sr." },
        { id: 170285, name: "Anthony Reichert" },
        { id: 845, name: "Miss Cayla Hyatt DVM" },
      ],
      initiated_at: "1994-01-07T14:19:05.000000Z",
      completed_at: "1976-07-19T06:02:22.000000Z",
    },
    {
      case_number: 14,
      user_id: 1,
      case_title: "dolore expedita possimus",
      case_title_formatted: "quia consequuntur blanditiis",
      case_status: "COMPLETED",
      processes: [
        { id: 96, name: "deleniti nam" },
        { id: 79, name: "totam aut" },
        { id: 142841245, name: "commodi quod" },
      ],
      requests: [
        { id: 346, name: "eum consequatur", parent_request: 78984 },
        {
          id: 353990,
          name: "aut dolorem deleniti",
          parent_request: 6272,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_0731", name: "quia excepturi ea aspernatur" },
        { id: "node_4862", name: "iure fugit sed" },
        { id: "node_6267", name: "repellendus fugiat" },
      ],
      participants: [
        { id: 76277, name: "Madonna Purdy" },
        { id: 28625426, name: "Mr. Timmothy Ankunding MD" },
        { id: 377993949, name: "Johann Stoltenberg" },
      ],
      initiated_at: "1972-11-14T13:12:41.000000Z",
      completed_at: "1989-03-29T06:06:42.000000Z",
    },
    {
      case_number: 15,
      user_id: 3,
      case_title: "adipisci quisquam nulla",
      case_title_formatted: "velit facere nihil",
      case_status: "IN_PROGRESS",
      processes: [
        { id: 770828, name: "recusandae saepe" },
        { id: 5361354, name: "quia voluptatem" },
        { id: 47187, name: "earum molestiae" },
      ],
      requests: [
        { id: 4160, name: "voluptas qui", parent_request: 50115 },
        {
          id: 3757,
          name: "sunt delectus perspiciatis",
          parent_request: 942749,
        },
      ],
      request_tokens: null,
      tasks: [
        { id: "node_7354", name: "commodi sint aliquid et" },
        { id: "node_0561", name: "quod similique eum" },
        { id: "node_1082", name: "soluta ut" },
      ],
      participants: [
        { id: 53865127, name: "Marcellus Bailey" },
        { id: 4, name: "Adrianna Leannon" },
        { id: 86, name: "Dr. Luis Miller Jr." },
      ],
      initiated_at: "1980-10-10T09:54:40.000000Z",
      completed_at: "1990-08-24T08:24:20.000000Z",
    },
  ],
  meta: {
    total: 1000, perPage: 15, currentPage: 1, lastPage: 67,
  },
});

export const getAllData = async ({ type, page, perPage }) => {
  const response = [];
  const allData = allCasesData();

  for (let index = 0; index < perPage; index += 1) {
    const idxLooper = index % (allData.data.length - 1);

    const item = allData.data[idxLooper];
    item.case_number = index + 100 * page;
    item.case_title = `${type} ${page} ${item.case_title}`;
    item.case_title_formatted = `${type} ${page} ${item.case_title_formatted}`;
    response.push(item);
  }

  return response;
};

const services = {
  completed: "get_completed",
  in_progress: "get_in_progress",
  all: "get_all_cases",
};

export const getCaseData = async (service, data) => {
  const response = await api.get(`/api/1.1/cases/${services[service] || "get_all_cases"}`, data);

  return response.data;
};

export const getCounters = async (data) => {
  const response = await api.get("/api/1.1/cases/get_my_cases_counters", data);

  return response.data;
};
