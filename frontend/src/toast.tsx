import { useEffect, useRef, ActionTooltip, t } from "./core";
import { createToastTimer } from "./toast-timer.mjs";

const duration: any = {
  success: 5000,
  info: 5000,
  warning: 7000,
  error: 8000,
};
const icons: any = {
  success: "yes-alt",
  info: "info",
  warning: "warning",
  error: "dismiss",
};

export function Toast({ message, onClose }: any) {
  const timer = useRef(null);
  const held = useRef({ hover: false, focus: false });
  useEffect(() => {
    const active = createToastTimer(
      onClose,
      message.duration || duration[message.status] || duration.info,
    );
    timer.current = active;
    if (held.current.hover) active.pause("hover");
    if (held.current.focus) active.pause("focus");
    return () => {
      active.cancel();
      timer.current = null;
    };
  }, [message]);

  return (
    <div
      className={"ar-toast ar-toast-" + message.status}
      role={message.status === "error" ? "alert" : "status"}
      onMouseEnter={() => {
        held.current.hover = true;
        timer.current?.pause("hover");
      }}
      onMouseLeave={() => {
        held.current.hover = false;
        timer.current?.resume("hover");
      }}
      onFocus={() => {
        held.current.focus = true;
        timer.current?.pause("focus");
      }}
      onBlur={(event) => {
        if (!event.currentTarget.contains(event.relatedTarget)) {
          held.current.focus = false;
          timer.current?.resume("focus");
        }
      }}
    >
      <span
        className={
          "ar-toast-icon dashicons dashicons-" +
          (icons[message.status] || icons.info)
        }
        aria-hidden="true"
      />
      <span className="ar-toast-text">{message.text}</span>
      <ActionTooltip text={t("Dismiss notification")}>
        <button
          type="button"
          className="ar-toast-close"
          aria-label={t("Dismiss notification")}
          onClick={onClose}
        >
          <span className="dashicons dashicons-no-alt" aria-hidden="true" />
        </button>
      </ActionTooltip>
    </div>
  );
}
