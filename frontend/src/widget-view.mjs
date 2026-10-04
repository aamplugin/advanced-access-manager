export function widgetViewForSubject(subjectType) {
  const visitor = subjectType === "visitor";
  return {
    endpoint: visitor ? "/widgets?area=frontend" : "/widgets",
    modeKey: visitor ? "*|frontend" : "*",
    modeArea: visitor ? "frontend" : null,
    modeScopes: visitor ? [] : ["frontend", "dashboard"],
  };
}
