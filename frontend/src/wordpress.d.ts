/** WordPress supplies these packages at runtime; do not bundle another React. */
declare const wp: {
  element: any;
  components: any;
  codeEditor?: any;
  CodeMirror?: any;
  apiFetch: (options: {
    path?: string;
    url?: string;
    method?: string;
    data?: unknown;
    signal?: AbortSignal;
    headers?: Record<string, string>;
  }) => Promise<any>;
  i18n: {
    __: (text: string, domain: string) => string;
    sprintf: (format: string, ...args: unknown[]) => string;
  };
};
interface Window {
  aamReactBootstrap: any;
  aamTermAccessBootstrap: any;
  aamPostAccessBootstrap?: any;
  aamPolicyAssigneeBootstrap?: any;
  aamReactIniEditorEnabled?: boolean;
}
declare namespace JSX {
  interface IntrinsicElements {
    [tag: string]: any;
  }
  interface ElementChildrenAttribute {
    children: {};
  }
  interface IntrinsicAttributes {
    key?: string | number;
    children?: any;
  }
}
