// datetime-local represents the browser's local time, without a timezone suffix.
export function localDateTime(value) {
  const date = value instanceof Date ? value : new Date(value);
  if (Number.isNaN(date.getTime())) return "";
  const pad = (part) => String(part).padStart(2, "0");
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

export function unixToLocalDateTime(value) {
  const seconds = Number(value);
  return Number.isFinite(seconds) && seconds > 0
    ? localDateTime(new Date(seconds * 1000))
    : "";
}

export function localDateTimeToUnix(value) {
  const milliseconds = new Date(value).getTime();
  return Number.isFinite(milliseconds) ? Math.floor(milliseconds / 1000) : 0;
}
