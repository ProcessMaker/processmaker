import { t } from "i18next";

export default {};

export const formatCounters = (data) => {
  const counters = [
    {
      header: t("My cases"),
      body: data.totalMyCases.toString(),
      color: "amber",
      icon: "far fa-user",
      url: "/cases",
    },
    {
      header: t("In progress"),
      body: data.totalInProgress.toString(),
      color: "green",
      icon: "fas fa-list",
      url: "/cases/in_progress",
    },
    {
      header: t("Completed"),
      body: data.totalCompleted.toString(),
      color: "blue",
      icon: "far fa-check-circle",
      url: "/cases/completed",
    },
  ];

  if (data.totalAllCases) {
    counters.push({
      header: t("All cases"),
      body: data.totalAllCases.toString(),
      color: "purple",
      icon: "far fa-clipboard",
      url: "/cases/all",
    });
  }

  if (data.totalMyRequest) {
    counters.push({
      header: t("My requests"),
      body: data.totalMyRequest.toString(),
      color: "gray",
      icon: "fas fa-play",
      url: () => {
        window.location.href = "/requests";
      },
    });
  }

  return counters;
};
