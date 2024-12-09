import { setGlobalPMVariable } from "../globalVariables";

import datetime_format from "../../data/datetime_formats.json";

const userID = document.head.querySelector("meta[name=\"user-id\"]");
const userFullName = document.head.querySelector("meta[name=\"user-full-name\"]");
const userAvatar = document.head.querySelector("meta[name=\"user-avatar\"]");
const formatDate = document.head.querySelector("meta[name=\"datetime-format\"]");
const timezone = document.head.querySelector("meta[name=\"timezone\"]");
const appUrl = document.head.querySelector("meta[name=\"app-url\"]");

setGlobalPMVariable("app", appUrl ? {
  url: appUrl.content,
} : null);

if (userID) {
  const user = {
    id: userID.content,
    datetime_format: formatDate?.content,
    calendar_format: formatDate?.content,
    timezone: timezone?.content,
    fullName: userFullName?.content,
    avatar: userAvatar?.content,
  };

  datetime_format.forEach((value) => {
    if (formatDate.content === value.format) {
      user.datetime_format = value.momentFormat;
      user.calendar_format = value.calendarFormat;
    }
  });

  setGlobalPMVariable("user", user);
}
