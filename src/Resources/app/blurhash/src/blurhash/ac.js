import { PI, PI2 } from './constants';

export function signSqr(x) {
  return (x < 0 ? -1 : 1) * x * x;
}

export function fastCos(x) {
  x += PI / 2;
  while (x > PI) {
    x -= PI2;
  }

  const cos = 1.27323954 * x - 0.405284735 * signSqr(x);
  return 0.225 * (signSqr(cos) - cos) + cos;
}
