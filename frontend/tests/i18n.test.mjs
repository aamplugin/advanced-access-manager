import test from "node:test";
import assert from "node:assert/strict";
import { createHash } from "node:crypto";
import { readFile } from "node:fs/promises";

test("the admin translation JSON matches WordPress's script path hash", async () => {
  const source = "media/js/react-admin.js";
  const hash = createHash("md5").update(source).digest("hex");
  const contents = await readFile(
    new URL(`../../lang/advanced-access-manager-fr_FR-${hash}.json`, import.meta.url),
    "utf8",
  );
  const translations = JSON.parse(contents);

  assert.equal(translations.source, source);
  assert.equal(translations.locale_data.messages.Delete[0], "Supprimer");
  assert.ok(translations.locale_data.messages["Geo lookup settings saved."]?.[0]);
});
