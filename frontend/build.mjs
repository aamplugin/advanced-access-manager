import { build } from "esbuild";
await build({
  entryPoints: ["src/index.tsx"],
  bundle: true,
  outfile: "../media/js/react-admin.js",
  format: "iife",
  target: ["es2020"],
  jsxFactory: "wp.element.createElement",
  jsxFragment: "wp.element.Fragment",
  minify: true,
  sourcemap: true,
  legalComments: "eof",
});
await build({
  entryPoints: ["src/post-metabox.tsx"],
  bundle: true,
  outfile: "../media/js/post-access-metabox.js",
  format: "iife",
  target: ["es2020"],
  jsxFactory: "wp.element.createElement",
  jsxFragment: "wp.element.Fragment",
  minify: true,
  sourcemap: true,
  legalComments: "eof",
});
await build({
  entryPoints: ["src/term-access.tsx"],
  bundle: true,
  outfile: "../media/js/term-access.js",
  format: "iife",
  target: ["es2020"],
  jsxFactory: "wp.element.createElement",
  jsxFragment: "wp.element.Fragment",
  minify: true,
  sourcemap: true,
  legalComments: "eof",
});
await build({
  entryPoints: ["src/policy-assignee-metabox.tsx"],
  bundle: true,
  outfile: "../media/js/policy-assignee-metabox.js",
  format: "iife",
  target: ["es2020"],
  jsxFactory: "wp.element.createElement",
  jsxFragment: "wp.element.Fragment",
  minify: true,
  sourcemap: true,
  legalComments: "eof",
});
await build({
  entryPoints: ["src/policy-document-ui.mjs"],
  bundle: true,
  outfile: "../media/js/policy-document.js",
  format: "iife",
  target: ["es2020"],
  minify: true,
  sourcemap: true,
  legalComments: "eof",
});
