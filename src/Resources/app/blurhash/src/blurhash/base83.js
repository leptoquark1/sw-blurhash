const base83digit = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz#$%*+,-.:;=?@[]^_{|}~';

export function decode83(str, start, end) {
  let value = 0;
  while (start < end) {
    value *= 83;
    value += base83digit.indexOf(str[start++]);
  }
  return value;
}
