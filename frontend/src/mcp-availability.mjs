export function mcpUnavailable(
  adapterAvailable,
  servers,
  tools,
  resources,
  prompts,
) {
  if (!adapterAvailable) return true;
  if (!servers.loaded || servers.error) return false;
  if (!Array.isArray(servers.data) || !servers.data.length) return true;

  const primitives = [tools, resources, prompts];
  return (
    primitives.every((collection) => collection.loaded && !collection.error) &&
    primitives.every(
      (collection) =>
        !Array.isArray(collection.data) || !collection.data.length,
    )
  );
}
