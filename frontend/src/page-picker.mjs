export function pageSearchPath(search) {
  const params = new URLSearchParams({
    per_page: "30",
    orderby: "title",
    order: "asc",
    _fields: "id,title",
  });
  if (search.trim()) params.set("search", search.trim());
  return "/wp/v2/pages?" + params.toString();
}

export function needsPageSelection(redirect) {
  return (
    redirect?.type === "page_redirect" && !Number(redirect.redirect_page_id)
  );
}
