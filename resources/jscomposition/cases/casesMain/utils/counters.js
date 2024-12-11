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
      id: "card-button-my-cases",
    },
    {
      header: t("In progress"),
      body: data.totalInProgress.toString(),
      color: "green",
      icon: "fas fa-list",
      url: "/cases/in_progress",
      id: "card-button-in-progress",
    },
    {
      header: t("Completed"),
      body: data.totalCompleted.toString(),
      color: "blue",
      icon: "far fa-check-circle",
      url: "/cases/completed",
      id: "card-button-completed",
    },
  ];

  if (data.totalAllCases !== null) {
    counters.push({
      header: t("All cases"),
      body: data.totalAllCases.toString(),
      color: "purple",
      icon: "far fa-clipboard",
      url: "/cases/all",
      id: "card-button-all",
    });
  }

  if (data.totalMyRequest !== null) {
    counters.push({
      header: t("My requests"),
      body: data.totalMyRequest.toString(),
      color: "gray",
      icon: "fas fa-play",
      id: "card-button-requests",
      url: () => {
        window.location.href = "/requests";
      },
    });
  }

  return counters;
};
