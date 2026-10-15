#!/usr/bin/env bash
# Chỉ dùng cho học tập cục bộ. Script không cài gói, không xóa dữ liệu.
set -u
root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)" || exit 2
cd "$root" || exit 2
lesson="1"
port="8101"

usage() {
  printf 'Cách dùng: bash lab.sh check | run | test | extend | db-config | sql\n'
  printf 'check: kiểm tra môi trường; run: chạy bài; test: kiểm tra mẫu; extend: kiểm tra mở rộng.\n'
  printf 'db-config và sql chỉ dùng cho Lab 05. Dừng máy chủ bằng Ctrl+C.\n'
}

need_php() {
  command -v php >/dev/null 2>&1 || { echo 'THIEU_PHP: xem mục Công cụ cần có trong Huong_dan.docx của bài lab'; return 2; }
  php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' || { echo 'CAN_PHP_8_1_TRO_LEN'; return 2; }
}

check_env() {
  need_php || return 2
  command -v git >/dev/null 2>&1 || { echo 'THIEU_GIT'; return 2; }
  if [[ "$lesson" == 5 ]]; then
    command -v psql >/dev/null 2>&1 || { echo 'THIEU_PSQL: xem tài liệu chuẩn bị Lab 05'; return 2; }
    php -r 'exit(extension_loaded("pgsql") ? 0 : 1);' || { echo 'THIEU_PGSQL: cài phần mở rộng đúng phiên bản PHP'; return 2; }
  fi
  printf 'MOI_TRUONG=SAN_SANG | Lab %s | PHP %s\n' "$lesson" "$(php -r 'echo PHP_VERSION;')"
}

case "${1:-}" in
  check)
    check_env
    ;;
  run)
    need_php || exit 2
    printf 'Khởi động máy chủ tại http://127.0.0.1:%s — Ctrl+C để dừng\n' "$port"
    php -S "127.0.0.1:$port" -t public
    ;;
  test)
    need_php || exit 2
    bash scripts/check-env.sh
    ;;
  extend)
    need_php || exit 2
    bash tests/extension.sh
    ;;
  *)
    usage
    exit 1
    ;;
esac
