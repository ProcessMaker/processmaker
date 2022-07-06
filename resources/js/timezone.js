export function timezone () {
  if (!window.ProcessMaker.user && !window.ProcessMaker.user.timezone) return new Error("User or Timezone hasn't been set");
  return window.ProcessMaker.user.timezone;
}

export function timezone_format () {
  if (!window.ProcessMaker.user && !window.ProcessMaker.user.datetime_format) return new Error("User or datetime_format hasn't been set");
  return window.ProcessMaker.user.datetime_format;
}
