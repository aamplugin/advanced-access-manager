import { validatePolicyJson, formatPolicyJson } from "./policy-document.mjs";
import { installErrorHandling } from "./error-report.mjs";

installErrorHandling();

const t = (text) => wp.i18n.__(text, "advanced-access-manager");

function initializePolicyDocument() {
  const textarea = document.getElementById("aam-policy-editor");
  const status = document.getElementById("aam-policy-status");
  if (!textarea || !status) return;

  let editor = null;
  if (window.aamPolicyEditorSettings && wp.codeEditor) {
    editor = wp.codeEditor.initialize(
      "aam-policy-editor",
      window.aamPolicyEditorSettings,
    ).codemirror;
  }

  const guide = document.getElementById("aam-policy-quick-guide");
  const guideToggle = document.getElementById("aam-policy-guide-toggle");
  const guideLabel = document.getElementById("aam-policy-guide-toggle-label");
  const guideIcon = document.getElementById("aam-policy-guide-toggle-icon");
  const layout = textarea.closest(".aam-policy-document-layout");
  const guidePreferenceKey = "aam-policy-quick-guide-hidden";
  const setGuideHidden = (hidden) => {
    if (!guide || !guideToggle || !layout) return;
    guide.hidden = hidden;
    layout.classList.toggle("is-guide-hidden", hidden);
    guideToggle.setAttribute("aria-expanded", String(!hidden));
    guideLabel.textContent = hidden ? t("Show guide") : t("Hide guide");
    guideIcon.className = `dashicons dashicons-${hidden ? "visibility" : "hidden"}`;
    if (editor) requestAnimationFrame(() => editor.refresh());
  };
  try {
    setGuideHidden(window.localStorage.getItem(guidePreferenceKey) === "true");
  } catch {
    setGuideHidden(false);
  }
  guideToggle?.addEventListener("click", () => {
    const hidden = !guide.hidden;
    setGuideHidden(hidden);
    try {
      window.localStorage.setItem(guidePreferenceKey, String(hidden));
    } catch {
      // The toggle still works when browser storage is unavailable.
    }
  });

  const read = () => (editor ? editor.getValue() : textarea.value);
  const write = (value) => {
    if (editor) {
      editor.setValue(value);
      editor.save();
    } else {
      textarea.value = value;
    }
  };
  const focusLocation = (result) => {
    if (editor) {
      editor.focus();
      if (result.line)
        editor.setCursor(
          result.line - 1,
          Math.max(0, (result.column || 1) - 1),
        );
    } else {
      textarea.focus();
      if (result.line) {
        const lines = textarea.value.split("\n");
        const offset = lines
          .slice(0, result.line - 1)
          .reduce((sum, line) => sum + line.length + 1, 0);
        textarea.setSelectionRange(
          offset + Math.max(0, (result.column || 1) - 1),
          offset + Math.max(0, (result.column || 1) - 1),
        );
      }
    }
  };
  const renderStatus = (result) => {
    status.replaceChildren();
    const warning = result.incomplete || result.resourceWarnings?.length;
    status.className = `aam-policy-document-status is-${result.valid ? (warning ? "warning" : "valid") : "invalid"}`;
    const icon = document.createElement("span");
    icon.className = `dashicons dashicons-${result.valid ? (warning ? "warning" : "yes-alt") : "dismiss"}`;
    icon.setAttribute("aria-hidden", "true");
    status.append(icon);

    const text = document.createElement("span");
    if (!result.valid) {
      text.textContent = result.line
        ? `${t("JSON error at line")} ${result.line}${result.column ? `, ${t("column")} ${result.column}` : ""}: ${result.message}`
        : `${t("JSON error")}: ${result.message}`;
    } else if (result.incomplete) {
      text.textContent = t(
        "JSON is valid. Add a Resource to each statement before using this policy.",
      );
    } else if (result.resourceWarnings?.length) {
      text.textContent = `${t("Check these resource names or actions before publishing:")} ${result.resourceWarnings.join(", ")}`;
    } else {
      const count = result.count;
      text.textContent = count
        ? `${t("JSON is valid")} · ${count} ${count === 1 ? t("statement") : t("statements")}`
        : t("JSON is valid. Add a Statement to define access rules.");
    }
    status.append(text);
    if (!result.valid && result.line) {
      const jump = document.createElement("button");
      jump.type = "button";
      jump.className = "button-link";
      jump.textContent = t("Go to error");
      jump.addEventListener("click", () => focusLocation(result));
      status.append(jump);
    }
  };
  const validate = () => {
    const result = validatePolicyJson(read(), window.aamPolicyKnownResources);
    renderStatus(result);
    return result;
  };

  if (editor) {
    editor.on("change", validate);
  } else {
    textarea.addEventListener("input", validate);
  }
  validate();

  document
    .getElementById("aam-policy-format")
    ?.addEventListener("click", () => {
      const result = validate();
      if (!result.valid) return focusLocation(result);
      write(formatPolicyJson(read()));
      validate();
      if (editor) editor.focus();
      else textarea.focus();
    });

  const form = textarea.closest("form");
  if (form) {
    const stopInvalidSave = (event) => {
      const result = validate();
      if (result.valid) {
        if (editor) editor.save();
        return;
      }
      event.preventDefault();
      event.stopImmediatePropagation();
      focusLocation(result);
    };
    form.addEventListener("submit", stopInvalidSave, true);
    form.addEventListener(
      "click",
      (event) => {
        if (event.target.closest("#publish, #save-post"))
          stopInvalidSave(event);
      },
      true,
    );
  }

  document
    .getElementById("aam-policy-copy-example")
    ?.addEventListener("click", async () => {
      const example = document.getElementById(
        "aam-policy-example-code",
      ).textContent;
      const feedback = document.getElementById("aam-policy-copy-status");
      try {
        if (navigator.clipboard?.writeText) {
          await navigator.clipboard.writeText(example);
        } else {
          const temporary = document.createElement("textarea");
          temporary.value = example;
          temporary.style.position = "fixed";
          temporary.style.opacity = "0";
          document.body.append(temporary);
          temporary.select();
          const copied = document.execCommand("copy");
          temporary.remove();
          if (!copied) throw new Error("Copy unavailable");
        }
        feedback.textContent = t(
          "Example copied. Adapt the resource before using it.",
        );
      } catch {
        feedback.textContent = t(
          "Copy unavailable. Select the example text to copy it.",
        );
      }
    });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializePolicyDocument);
} else {
  initializePolicyDocument();
}
