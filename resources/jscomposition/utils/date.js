import moment from "moment";

export const formatDate = (value, format) => {
  let config = "DD/MM/YYYY hh:mm";
  if (
    typeof ProcessMaker !== "undefined"
    && ProcessMaker.user
    && ProcessMaker.user.datetime_format
  ) {
    if (format === "datetime") {
      config = ProcessMaker.user.datetime_format;
    }
    if (format === "date") {
      config = ProcessMaker.user.datetime_format.replace(
        /[\sHh:msaAzZ]/g,
        "",
      );
    }
  }
  if (value) {
    if (moment(value).isValid()) {
      return moment(value).format(config);
    }
    return value;
  }
  return "-";
};
