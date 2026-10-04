import { build } from "esbuild";
import { parse } from "@babel/parser";
import { readFile, writeFile } from "node:fs/promises";
import { resolve } from "node:path";

const bundles = [
  ["src/index.tsx", "media/js/react-admin.js"],
  ["src/post-metabox.tsx", "media/js/post-access-metabox.js"],
  ["src/term-access.tsx", "media/js/term-access.js"],
  ["src/policy-assignee-metabox.tsx", "media/js/policy-assignee-metabox.js"],
  ["src/policy-document-ui.mjs", "media/js/policy-document.js"],
];
const output = resolve("../lang/advanced-access-manager-frontend.pot");
const catalog = new Map();
const dynamic = [];
const literalProperties = new Set([
  "label", "title", "description", "note", "resetLabel", "placeholder",
  "help", "serviceName", "template", "detail",
]);
const literalContainers = new Set([
  "names", "resetLabels", "descriptions", "eventLabels", "defaultDetails",
  "highlights",
]);

function literalValues(node) {
  if (!node) return [];
  if (node.type === "StringLiteral") {
    return [node.value];
  }
  if (node.type === "TemplateLiteral" && node.expressions.length === 0) {
    return [node.quasis[0].value.cooked];
  }
  if (node.type === "ParenthesizedExpression") return literalValues(node.expression);
  if (node.type === "ConditionalExpression") {
    return [...literalValues(node.consequent), ...literalValues(node.alternate)];
  }
  if (node.type === "LogicalExpression") {
    return [...literalValues(node.left), ...literalValues(node.right)];
  }
  return [];
}

function add(value, bundle, source, node) {
  if (!value.trim()) return;
  const entry = catalog.get(value) || { bundles: new Set(), sources: new Set() };
  entry.bundles.add(bundle);
  entry.sources.add(`frontend/${source}:${node.loc.start.line}`);
  catalog.set(value, entry);
}

function nestedLiterals(node) {
  if (!node) return [];
  const direct = literalValues(node);
  if (direct.length) return direct;
  if (node.type === "ObjectExpression") {
    return node.properties.flatMap((property) =>
      property.type === "ObjectProperty" ? nestedLiterals(property.value) : []
    );
  }
  if (node.type === "ArrayExpression") {
    return node.elements.flatMap((element) => nestedLiterals(element));
  }
  if (node.type === "LogicalExpression") {
    return [...nestedLiterals(node.left), ...nestedLiterals(node.right)];
  }
  if (node.type === "ConditionalExpression") {
    return [...nestedLiterals(node.consequent), ...nestedLiterals(node.alternate)];
  }
  if (node.type === "MemberExpression") return nestedLiterals(node.object);
  return [];
}

for (const [entryPoint, bundle] of bundles) {
  const result = await build({
    entryPoints: [entryPoint],
    bundle: true,
    write: false,
    metafile: true,
    outfile: `../${bundle}`,
    format: "iife",
    target: ["es2020"],
    jsxFactory: "wp.element.createElement",
    jsxFragment: "wp.element.Fragment",
  });
  for (const source of Object.keys(result.metafile.inputs)) {
    if (!source.startsWith("src/")) continue;
    const body = await readFile(source, "utf8");
    const sourceFile = parse(body, {
      sourceType: "module",
      plugins: source.endsWith(".tsx") ? ["typescript", "jsx"] : [],
    });
    const visit = (node) => {
      if (node.type === "CallExpression" && node.callee.type === "Identifier"
        && node.callee.name === "t" && node.arguments.length) {
        const strings = literalValues(node.arguments[0]);
        if (strings.length) {
          for (const value of strings) add(value, bundle, source, node.arguments[0]);
        } else {
          dynamic.push(`frontend/${source}:${node.loc.start.line}`);
        }
      }
      if (source === "src/mutation-toast.mjs" && node.type === "CallExpression"
        && node.callee.type === "Identifier" && node.callee.name === "result") {
        for (const argument of node.arguments) {
          for (const value of literalValues(argument)) {
            add(value, bundle, source, argument);
          }
        }
      }
      if (node.type === "ObjectProperty"
        && literalProperties.has(node.key.name || node.key.value)) {
        for (const value of literalValues(node.value)) {
          add(value, bundle, source, node.value);
        }
      }
      if (node.type === "JSXAttribute"
        && literalProperties.has(node.name.name)
        && node.value?.type === "StringLiteral") {
        add(node.value.value, bundle, source, node.value);
      }
      if (node.type === "AssignmentExpression"
        && node.left.type === "MemberExpression"
        && literalProperties.has(node.left.property.name)) {
        for (const value of literalValues(node.right)) {
          add(value, bundle, source, node.right);
        }
      }
      if (node.type === "VariableDeclarator" && node.id.type === "Identifier"
        && (literalContainers.has(node.id.name)
          || (source === "src/mutation-toast.mjs"
            && ["item", "access", "label"].includes(node.id.name)))) {
        for (const value of nestedLiterals(node.init)) {
          add(value, bundle, source, node.init);
        }
      }
      for (const value of Object.values(node)) {
        if (Array.isArray(value)) {
          for (const child of value) {
            if (child && typeof child.type === "string") visit(child);
          }
        } else if (value && typeof value.type === "string") {
          visit(value);
        }
      }
    };
    visit(sourceFile);
  }
}

const quote = (value) => JSON.stringify(value);
const lines = [
  'msgid ""',
  'msgstr ""',
  '"Content-Type: text/plain; charset=UTF-8\\n"',
  '"X-Domain: advanced-access-manager\\n"',
  "",
];
for (const [value, entry] of [...catalog].sort(([a], [b]) => a.localeCompare(b))) {
  for (const source of [...entry.sources].sort()) lines.push(`#. Source: ${source}`);
  lines.push(`#: ${[...entry.bundles].sort().join(" ")}`);
  lines.push(`msgid ${quote(value)}`);
  lines.push('msgstr ""', "");
}
await writeFile(output, lines.join("\n"));
console.log(`${catalog.size} frontend messages extracted to ${output}`);
console.log(`${new Set(dynamic).size} dynamic t() calls need review`);
if (dynamic.length) console.log([...new Set(dynamic)].join("\n"));
