import { D, E } from './constants';

export function sRGBToLinear(value) {
  return value > 10.31475 ? Math.pow(value / E + 0.052132, 2.4) : value / D;
}

export function linearTosRGB(v) {
  return ~~(v > 0.00001227 ? E * Math.pow(v, 0.416666) - 13.025 : v * D + 1);
}
