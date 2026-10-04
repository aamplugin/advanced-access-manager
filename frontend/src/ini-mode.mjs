/** Register ConfigPress highlighting on WordPress's bundled CodeMirror. */
export function registerIniMode(CodeMirror) {
  CodeMirror.defineMode("aam-ini", () => ({
    startState: () => ({ afterEquals: false }),
    token(stream, state) {
      if (stream.sol()) {
        state.afterEquals = false;
        if (stream.match(/^\s*[;#].*/)) return "comment";
        if (stream.match(/^\s*\[[^\]]+\]/)) return "header";
      }
      if (stream.eatSpace()) return null;
      if (!state.afterEquals) {
        if (stream.match(/^[^=;#\s][^=;#]*(?=\s*=)/)) return "property";
        if (stream.match(/^=/)) {
          state.afterEquals = true;
          return "operator";
        }
      } else {
        if (stream.match(/^"(?:\\.|[^"\\])*"/)) return "string";
        if (stream.match(/^'(?:\\.|[^'\\])*'/)) return "string";
        if (stream.match(/^(?:true|false|on|off|yes|no|null)\b/i))
          return "atom";
        if (stream.match(/^-?\d+(?:\.\d+)?\b/)) return "number";
        if (stream.match(/^\S+/)) return "string";
      }
      stream.next();
      return null;
    },
  }));
}
