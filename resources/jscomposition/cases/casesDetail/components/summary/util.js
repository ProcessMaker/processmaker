import moment from "moment-timezone";

/**
 * Formats date and datetime values in a summary object array
 *
 * @param {Array} summary - Array of objects containing type, key and value properties
 * @param {string} summary[].type - Type of the value ('datetime', 'date' or other)
 * @param {string} summary[].key - Key name for the formatted value
 * @param {string} summary[].value - Value to be formatted
 *
 * @returns {Object} Object with formatted values mapped to their keys
 *
 * For datetime type: formats to "MM/DD/YYYY HH:mm" in user's timezone
 * For date type: formats to "MM/DD/YYYY" in user's timezone
 * For other types: returns original value unchanged
 */

export const dateFormatSummary = (summary) => {
  const formatDate = (value, format) => moment(value)
    .tz(window.ProcessMaker.user.timezone)
    .format(format);

  const dateFormats = {
    datetime: "MM/DD/YYYY HH:mm",
    date: "MM/DD/YYYY",
  };

  return summary.reduce((options, { type, key, value }) => {
    options[key] = dateFormats[type]
      ? formatDate(value, dateFormats[type])
      : value;
    return options;
  }, {});
};
