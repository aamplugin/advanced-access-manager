import test from "node:test";
import assert from "node:assert/strict";
import {
  localDateTime,
  localDateTimeToUnix,
  unixToLocalDateTime,
} from "../src/date-time.mjs";

test("date-time picker values preserve the instant in the browser timezone", () => {
  const originalTimezone = process.env.TZ;
  process.env.TZ = "America/New_York";
  try {
    const iso = "2027-01-01T12:34:56-05:00";
    const local = "2027-01-01T12:34:56";
    const timestamp = Date.parse(iso) / 1000;

    assert.equal(localDateTime(iso), local);
    assert.equal(unixToLocalDateTime(timestamp), local);
    assert.equal(localDateTimeToUnix(local), timestamp);
    assert.equal(new Date(local).toISOString(), "2027-01-01T17:34:56.000Z");
  } finally {
    if (originalTimezone === undefined) delete process.env.TZ;
    else process.env.TZ = originalTimezone;
  }
});

test("empty and invalid stored expiration values leave the picker blank", () => {
  assert.equal(localDateTime("invalid"), "");
  assert.equal(unixToLocalDateTime(0), "");
  assert.equal(unixToLocalDateTime("invalid"), "");
  assert.equal(localDateTimeToUnix(""), 0);
});
