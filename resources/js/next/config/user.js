import { getGlobalVariable, setGlobalPMVariables } from "../globalVariables";
import datetime_format from "../../data/datetime_formats.json";

// Profile settings store PHP date tokens; Moment uses a different token set.
export const momentFormatsFor = (phpFormat) => {
  const match = datetime_format.find((value) => value.format === phpFormat);
  if (!match) {
    return null;
  }
  return {
    datetime_format: match.momentFormat,
    calendar_format: match.calendarFormat,
  };
};

export const withMomentDateFormats = (user) => {
  if (!user || typeof user !== "object") {
    return user;
  }
  const formats = momentFormatsFor(user.datetime_format);
  if (!formats) {
    return user;
  }
  return {
    ...user,
    datetime_format: formats.datetime_format,
    calendar_format: formats.calendar_format,
  };
};

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

    const formats = momentFormatsFor(formatDate?.content);
    if (formats) {
      user.datetime_format = formats.datetime_format;
      user.calendar_format = formats.calendar_format;
    }

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
