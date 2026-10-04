import {
  useState,
  useEffect,
  ComboboxControl,
  useWorkspace,
  request,
  plain,
  t,
} from "./core";
import { pageSearchPath } from "./page-picker.mjs";

function pageOption(page: any) {
  return {
    value: String(page.id),
    label: `${plain(page.title?.rendered) || t("(Untitled)")} · #${page.id}`,
  };
}

export function PagePicker({ value, onChange }: any) {
  const { setMessage } = useWorkspace();
  const selectedId = value ? String(value) : null;
  const [search, setSearch] = useState("");
  const [options, setOptions] = useState([]);
  const [selected, setSelected] = useState(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const controller = new AbortController();
    let live = true;
    const timer = setTimeout(
      async () => {
        setLoading(true);
        try {
          const pages = await request(pageSearchPath(search), {
            signal: controller.signal,
          });
          if (live) setOptions(pages.map(pageOption));
        } catch (failure) {
          if (live && failure.name !== "AbortError") {
            setOptions([]);
            setMessage({
              status: "error",
              text: t("Pages could not be loaded. Try searching again."),
            });
          }
        } finally {
          if (live) setLoading(false);
        }
      },
      search ? 250 : 0,
    );
    return () => {
      live = false;
      clearTimeout(timer);
      controller.abort();
    };
  }, [search]);

  useEffect(() => {
    if (!selectedId || selected?.value === selectedId) return;
    const controller = new AbortController();
    let live = true;
    request(`/wp/v2/pages/${encodeURIComponent(selectedId)}?_fields=id,title`, {
      signal: controller.signal,
    })
      .then((page) => {
        if (live) setSelected(pageOption(page));
      })
      .catch(() => {
        if (live)
          setSelected({
            value: selectedId,
            label: `${t("Page")} #${selectedId}`,
          });
      });
    return () => {
      live = false;
      controller.abort();
    };
  }, [selectedId]);

  const current = selectedId
    ? options.find((option) => option.value === selectedId) ||
      (selected?.value === selectedId ? selected : null) || {
        value: selectedId,
        label: `${t("Page")} #${selectedId}`,
      }
    : null;
  const available = current
    ? [current, ...options.filter((option) => option.value !== current.value)]
    : options;

  return (
    <div className="ar-page-picker">
      <ComboboxControl
        label={t("Page")}
        value={selectedId}
        options={available}
        isLoading={loading}
        placeholder={t("Search pages by title…")}
        help={t("Type a title to search all published pages.")}
        onFilterValueChange={setSearch}
        onChange={(next) => {
          setSelected(
            available.find((option) => option.value === next) || null,
          );
          onChange(next ? Number(next) : null);
        }}
      />
    </div>
  );
}
