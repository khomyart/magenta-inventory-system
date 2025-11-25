import momentWithTimezone from "moment-timezone";
import moment from "moment";

const timeMasks = {
  locales: {
    "ua": "DD/MM/YYYY",
  },
  server: "YYYY-MM-DD HH:mm"
}

/**
 *
 * @param locale available time masks placed in timeMasks.locales
 * @param addHoursAndMinutes
 * @param addSeconds
 * @returns {string}
 */
function getTimeFormatDependsOnLocale(locale, addHoursAndMinutes = false, addSeconds = false) {
  let tmpTimeMask = "";
  switch (locale) {
    case "ua":
      tmpTimeMask = timeMasks.locales.ua;
      break;
    default:
      tmpTimeMask = timeMasks.locales.ua;
  }
  if (addHoursAndMinutes) {
    tmpTimeMask += " HH:mm"
  }
  if (addSeconds) {
    tmpTimeMask += " :ss"
  }
  return tmpTimeMask
}

/**
 * Converts server time zone to client time zone.
 * Only time zone. The format is still the same.
 *
 * @param serverTime
 * @returns {string}
 */
function transformServerTimeToClientTime(serverTime) {
  const serverTimeFormat = timeMasks.server;

  const serverTimezone = "Europe/Kyiv";
  const clientTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  let serverTimeWithTz = momentWithTimezone.tz(serverTime, serverTimezone);
  return serverTimeWithTz.clone().tz(clientTimezone).format(serverTimeFormat);
}

/**
 * Convert client's time to the server time.
 * Only time zone. The Format is still the same.
 *
 * @param clientTime client's time including its TZ.
 * @param locale client's current locale (ua|en|etc.)
 * @returns {string} server time (shifted to server timezone) but in format of client's time
 */
function transformClientTimeToServerTime(clientTime, locale) {
  let timeFormat = getTimeFormatDependsOnLocale(locale) + " HH:mm:ss";
  let formattedClientTime = moment(clientTime, timeFormat).format(timeMasks.server)

  const serverTimezone = "Europe/Kyiv";
  const clientTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  let clientTimeWithTz = momentWithTimezone.tz(formattedClientTime, clientTimezone);
  return clientTimeWithTz.clone().tz(serverTimezone).format(timeFormat);
}

/**
 * Returns user's time depends on its locale
 *
 * @param serverTime time in format: YYYY-MM-DD HH:mm:ss
 * @param locale locale in format; 'en' | 'ua'
 * @param includeHoursAndMinutes display hours and minutes or not
 * @param includeSeconds display seconds or not
 *
 * @returns {string} date and time in format related to user locale and timezone
 */
function getClientTime(serverTime, locale, includeHoursAndMinutes = true, includeSeconds = false) {
  let convertedServerTime = transformServerTimeToClientTime(serverTime);
  let serverTimeFormat = timeMasks.server;
  let localeTimeFormat = getTimeFormatDependsOnLocale(locale);

  localeTimeFormat = includeHoursAndMinutes ? localeTimeFormat + " HH:mm" : localeTimeFormat;
  localeTimeFormat = includeHoursAndMinutes && includeSeconds ? localeTimeFormat + ":ss" : localeTimeFormat;

  let time = moment(convertedServerTime, serverTimeFormat);
  return time.format(localeTimeFormat);
}

/**
 * Return server time depends on client's time + its locale
 *
 * @param clientTime time in client locale format
 * @param locale locale in format; 'en' | 'ua'
 * @param includeHoursAndMinutes display hours and minutes or not
 *
 * @param includeSeconds display seconds or not
 *
 * @returns {string} date and time in format related to user locale and timezone
 */
function getServerTime(clientTime, locale, includeHoursAndMinutes = true, includeSeconds = false) {
  let localeTimeFormat = getTimeFormatDependsOnLocale(locale);
  localeTimeFormat = includeHoursAndMinutes ? localeTimeFormat + " HH:mm" : localeTimeFormat;
  localeTimeFormat = includeHoursAndMinutes && includeSeconds ? localeTimeFormat + ":ss" : localeTimeFormat;

  let time = moment(clientTime, localeTimeFormat);
  let convertedClientTime = time.format(localeTimeFormat);
  let serverTime = transformClientTimeToServerTime(convertedClientTime, locale);
  return moment(serverTime, localeTimeFormat).format(timeMasks.server);
}

export {
  getClientTime,
  getServerTime,
  getTimeFormatDependsOnLocale,
};
