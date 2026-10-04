export function contentItemsPath(active, page, search) {
  const isPosts = active.kind === "posts";
  const params = new URLSearchParams();
  if (isPosts) {
    params.set("post_type", active.postType);
  } else {
    params.set("taxonomy", active.taxonomy);
    if (active.postType) params.set("post_type", active.postType);
  }
  params.set("per_page", "20");
  params.set("offset", String(page * 20));
  params.set("search", search);
  return (isPosts ? "/posts?" : "/terms?") + params.toString();
}

export function contentResourcePath(target) {
  const id = target.kind === "term" ? target.row.id : target.row.slug;
  const base = "/content/" + target.kind + "/" + encodeURIComponent(String(id));
  if (target.kind !== "term") return base;
  const params = new URLSearchParams({ taxonomy: target.row.taxonomy });
  if (target.postType) params.set("post_type", target.postType);
  return base + "?" + params.toString();
}
