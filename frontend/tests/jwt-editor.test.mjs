import test from "node:test";
import assert from "node:assert/strict";
import {
  parseClaimsJson,
  parseClaimRows,
  claimsToRows,
} from "../src/jwt-editor.mjs";
import { expirationPayload } from "../src/expiration.mjs";

test("quick expiration choices use supported relative periods", () => {
  assert.deepEqual(expirationPayload("day", ""), { expires_in: "24 hours" });
  assert.deepEqual(expirationPayload("month", ""), { expires_in: "1 month" });
});

test("custom expiration requires a future instant and sends UTC", () => {
  const now = Date.parse("2026-09-24T12:00:00Z");
  assert.deepEqual(
    expirationPayload("custom", "2026-09-24T14:00:00-04:00", now),
    {
      expires_at: "2026-09-24T18:00:00.000Z",
    },
  );
  assert.throws(
    () => expirationPayload("custom", "2026-09-24T11:59:00Z", now),
    /future date/,
  );
});

test("claim rows retain value types and reject duplicate properties", () => {
  const rows = [
    { key: "scope", value: "read", type: "text" },
    { key: "limit", value: "5", type: "number" },
    { key: "active", value: "false", type: "boolean" },
    { key: "regions", value: '["us"]', type: "json" },
  ];
  const claims = parseClaimRows(rows);
  assert.deepEqual(claims, {
    scope: "read",
    limit: 5,
    active: false,
    regions: ["us"],
  });
  assert.deepEqual(parseClaimRows(claimsToRows(claims)), claims);
  assert.throws(() => parseClaimRows([...rows, rows[0]]), /more than once/);
});

test("JSON claims require an object", () => {
  assert.deepEqual(parseClaimsJson('{"scope":"read"}'), { scope: "read" });
  assert.throws(() => parseClaimsJson("[]"), /JSON object/);
});
