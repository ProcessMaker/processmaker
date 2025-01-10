import { getGlobalVariable, setGlobalPMVariables } from "../globalVariables";
import datetime_format from "../../data/datetime_formats.json";

export default () => {
  const moment = getGlobalVariable("moment");
  const userID = document.head.querySelector("meta[name=\"user-id\"]");
  const userFullName = document.head.querySelector("meta[name=\"user-full-name\"]");
  const userAvatar = document.head.querySelector("meta[name=\"user-avatar\"]");
  const formatDate = document.head.querySelector("meta[name=\"datetime-format\"]");
  const timezone = document.head.querySelector("meta[name=\"timezone\"]");
  const appUrl = document.head.querySelector("meta[name=\"app-url\"]");

  const app = appUrl ? {
    url: appUrl.content,
  } : null;

  let user;
  if (userID) {
    user = {
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

    if (user) {
      moment.tz.setDefault(user.timezone);
      moment.defaultFormat = user.datetime_format;
      moment.defaultFormatUtc = user.datetime_format;
    }

    if (document.documentElement.lang) {
      moment.locale(document.documentElement.lang);
      user.lang = document.documentElement.lang;
    }
  }

  setGlobalPMVariables({
    user,
    app,
  });
};
