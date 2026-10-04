import { createHash } from "node:crypto";
import { readdir, readFile, writeFile, unlink } from "node:fs/promises";
import { resolve } from "node:path";

const directory = resolve("../lang");
// WP-CLI 2.12 shortens react-admin.js to react-a.js while making JSON.
// WordPress hashes the actual registered script path when loading translations.
const brokenSource = "media/js/react-a.js";
const actualSource = "media/js/react-admin.js";
for (const name of await readdir(directory)) {
  if (!/^advanced-access-manager-[a-zA-Z_]+-[a-f0-9]{32}\.json$/.test(name)) {
    continue;
  }
  const path = resolve(directory, name);
  const data = JSON.parse(await readFile(path, "utf8"));
  const messages = data.locale_data?.messages || {};
  for (const [key, translations] of Object.entries(messages)) {
    if (key && (!Array.isArray(translations)
      || translations.every((translation) => !translation))) {
      delete messages[key];
    }
  }
  if (Object.keys(messages).length <= 1) {
    await unlink(path);
    continue;
  }

  if (data.source === brokenSource) {
    const hash = createHash("md5").update(actualSource).digest("hex");
    const correctedName = name.replace(/-[a-f0-9]{32}\.json$/, `-${hash}.json`);
    data.source = actualSource;
    await writeFile(resolve(directory, correctedName), `${JSON.stringify(data)}\n`);
    await unlink(path);
  } else {
    await writeFile(path, `${JSON.stringify(data)}\n`);
  }
}
