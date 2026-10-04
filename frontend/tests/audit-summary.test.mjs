import test from "node:test";
import assert from "node:assert/strict";
import {
  normalizeAuditSummary,
  safeAuditReference,
} from "../src/audit-summary.mjs";

test("audit summary accepts readable sections and safe references", () => {
  assert.deepEqual(
    normalizeAuditSummary({
      summary: "  Review elevated access. ",
      critical: ["Review administrators", null, ""],
      recommendations: ["Remove unused privileges"],
      references: [
        "https://aamportal.com/article/example",
        "javascript:alert(1)",
        "https://user:password@example.com/private",
      ],
    }),
    {
      summary: "Review elevated access.",
      critical: ["Review administrators"],
      concerns: [],
      recommendations: ["Remove unused privileges"],
      references: ["https://aamportal.com/article/example"],
    },
  );
});

test("empty or malformed summaries are rejected", () => {
  assert.equal(
    normalizeAuditSummary({ status: "failure", reason: "Try again" }),
    null,
  );
  assert.equal(normalizeAuditSummary(null), null);
  assert.equal(safeAuditReference("/relative-path"), null);
});
