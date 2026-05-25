/* eslint-disable import/prefer-default-export */
import { DateTime } from 'luxon';

/**
 * Convert PHP/moment date format to Luxon format
 * @param {string} format - PHP/moment format string
 * @returns {string} - Luxon format string
 */
const convertToLuxonFormat = (format) => {
  // Common conversions from PHP/moment to Luxon
  const replacements = {
    YYYY: 'yyyy',
    YY: 'yy',
    MM: 'LL',
    M: 'L',
    DD: 'dd',
    D: 'd',
    HH: 'HH',
    hh: 'hh',
    H: 'H',
    h: 'h',
    mm: 'mm',
    m: 'm',
    ss: 'ss',
    s: 's',
    A: 'a',
    a: 'a',
  };

  let luxonFormat = format;
  Object.entries(replacements).forEach(([from, to]) => {
    luxonFormat = luxonFormat.replace(new RegExp(from, 'g'), to);
  });

  return luxonFormat;
};

/**
 * Format date to user's date format
 * @param {string} value - The date to format
 * @returns {string} - The formatted date
 */
export const dateFormatter = (value) => {
  let datetimeConfig = 'dd/LL/yyyy hh:mm';
  let timezoneConfig = 'UTC';

  if (
    typeof ProcessMaker !== 'undefined'
    && ProcessMaker.user
    && ProcessMaker.user.datetime_format
  ) {
    timezoneConfig = ProcessMaker.user.timezone;
    datetimeConfig = convertToLuxonFormat(ProcessMaker.user.datetime_format);
  }

  if (value) {
    const date = DateTime.fromISO(value, { zone: 'utc' }).setZone(timezoneConfig);

    if (date.isValid) {
      return date.toFormat(datetimeConfig);
    }

    // Try parsing as SQL format
    const sqlDate = DateTime.fromSQL(value, { zone: 'utc' }).setZone(timezoneConfig);
    if (sqlDate.isValid) {
      return sqlDate.toFormat(datetimeConfig);
    }

    return value;
  }

  return '-';
};

