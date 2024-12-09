import { getGlobalPMVariable, getGlobalVariable } from "../globalVariables";

const moment = getGlobalVariable("moment");
const user = getGlobalPMVariable("user");

if (user) {
  moment.tz.setDefault(user.timezone);
  moment.defaultFormat = user.datetime_format;
  moment.defaultFormatUtc = user.datetime_format;
}

if (document.documentElement.lang) {
  moment.locale(document.documentElement.lang);
  user.lang = document.documentElement.lang;
}
