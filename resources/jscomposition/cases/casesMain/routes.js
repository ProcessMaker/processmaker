import CasesMain from "./CasesMain.vue";
import CasesDataSection from "./CasesDataSection.vue";

export default {};

export const routes = [
  {
    name: "cases",
    path: "/cases",
    component: CasesMain,
    props(route) {
      return {};
    },
    children: [
      {
        name: "cases-request",
        path: ":id?",
        component: CasesDataSection,
        props(route) {
          return {
            listId: route.params?.id || "",
          };
        },
      },
    ],
  },
];
