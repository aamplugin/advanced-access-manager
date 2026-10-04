import { readFile, writeFile } from "node:fs/promises";

const [phpPath, mergedPath, outputPath] = process.argv.slice(2);
if (!phpPath || !mergedPath || !outputPath) {
  throw new Error("Usage: node merge-pot.mjs PHP_POT MERGED_POT OUTPUT_POT");
}

function splitHeader(contents) {
  const start = contents.indexOf('msgid ""');
  const end = contents.indexOf("\n\n", start);
  if (start < 0 || end < 0) throw new Error("Invalid POT header");
  return [contents.slice(0, end + 2), contents.slice(end + 2)];
}

const [phpHeader] = splitHeader(await readFile(phpPath, "utf8"));
const [, messages] = splitHeader(await readFile(mergedPath, "utf8"));
await writeFile(
  outputPath,
  phpHeader + messages.replace(/^#\. #-#-#-#-#.*\n/gm, ""),
);
