export default {};
import { t } from "i18next";

export const formatCounters = (data) => {
  const counters = [
    {
      header: t("My cases"),
      body: data.myCases.toString(),
      color: "amber",
      icon: "far fa-user",
      url: "/cases-main/my-cases",
    },
    {
      header: t("In progress"),
      body: data.inProgress.toString(),
      color: "green",
      icon: "fas fa-list",
      url: "/cases-main/in-progress",
    },
    {
      header: t("Completed"),
      body: data.completed.toString(),
      color: "blue",
      icon: "far fa-check-circle",
      url: "/cases-main/completed",
    },
    {
      header: t("All cases"),
      body: data.allCases.toString(),
      color: "purple",
      icon: "far fa-clipboard",
      url: "/cases-main/all-cases",
    },
    {
      header: t("All requests"),
      body: data.allRequests.toString(),
      color: "gray",
      icon: "fas fa-play",
      url: () => {
        window.location.href = "/cases";
      },
    },
  ];

  return counters;
};
