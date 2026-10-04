import test from "node:test";
import assert from "node:assert/strict";
import {
  passwordlessPath,
  userExpirationPayload,
} from "../src/user-access.mjs";

test("passwordless issuance targets the chosen user and requests a signed URL", () => {
  assert.equal(
    passwordlessPath(42),
    "/aam/v2/jwts?user_id=42&fields=signed_url",
  );
  assert.throws(() => passwordlessPath("not-a-user"));
});

test("temporary access sends the role field consumed by the user model", () => {
  assert.deepEqual(
    userExpirationPayload(
      "2026-09-24T16:00:00.000Z",
      "change_role",
      "subscriber",
    ),
    {
      expiration: {
        expires_at: "2026-09-24T16:00:00.000Z",
        trigger: { type: "change_role", to_role: "subscriber" },
      },
    },
  );
  assert.equal(
    userExpirationPayload("2026-09-24T16:00:00.000Z", "lock", "").expiration
      .trigger,
    "lock",
  );
});
