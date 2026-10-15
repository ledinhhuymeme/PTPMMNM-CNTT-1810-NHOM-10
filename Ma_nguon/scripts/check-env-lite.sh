#!/usr/bin/env bash
# Kiểm tra môi trường chạy được (không cài thêm).
set -u
need() {
  command -v "$1" >/dev/null 2>&1 || { printf 'THIEU_%s\n' "${1^^}"; exit 2; }
}
need php
need git
php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' || { echo 'CAN_PHP_8_1_TRO_LEN'; exit 2; }
printf 'MOI_TRUONG=SAN_SANG\n'
