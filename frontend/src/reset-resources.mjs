export async function resetResources(endpoints, reset) {
  let succeeded = true;

  for (const endpoint of endpoints) {
    if (!(await reset(endpoint))) succeeded = false;
  }

  return succeeded;
}
