import { t } from "i18next";

export default {};

export const formatCounters = (data) => {
  const counters = [
    {
      header: t("My cases"),
      body: data.myCases.toString(),
      color: "amber",
      icon: "far fa-user",
      url: "/cases",
    },
    {
      header: t("In progress"),
      body: data.inProgress.toString(),
      color: "green",
      icon: "fas fa-list",
      url: "/cases/in_progress",
    },
    {
      header: t("Completed"),
      body: data.completed.toString(),
      color: "blue",
      icon: "far fa-check-circle",
      url: "/cases/completed",
    },
  ];

  if (data.allCases) {
    counters.push({
      header: t("All cases"),
      body: data.allCases.toString(),
      color: "purple",
      icon: "far fa-clipboard",
      url: "/cases/all",
    });
  }

  if (data.allRequests) {
    counters.push({
      header: t("My requests"),
      body: data.allRequests.toString(),
      color: "gray",
      icon: "fas fa-play",
      url: () => {
        window.location.href = "/requests";
      },
    });
  }

  return counters;
};
