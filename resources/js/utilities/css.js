export function classNames(...classes) {
  return classes.filter(Boolean).join(" ");
}

export function variationName(name, compliance) {
  return compliance[name];
}
