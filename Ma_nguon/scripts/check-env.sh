#!/usr/bin/env bash
# Script kiểm tra môi trường: PHP, Git và các tệp của dự án CommunityPass.
# Phần mẫu có hướng dẫn - sinh viên đọc và thực hiện lại từng bước theo phần IV.
set -u
root="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)" || exit 2
fail=0

# Hàm kiểm tra công cụ (lệnh) có tồn tại trong PATH không.
check_tool() {
  if command -v "$1" >/dev/null 2>&1; then
    printf 'OK tool %s\n' "$1"
  else
    printf 'MISSING tool %s\n' "$1" >&2
    fail=$((fail + 1))
  fi
}

# Hàm kiểm tra tệp có tồn tại không.
check_file() {
  if [[ -f "$root/$1" ]]; then
    printf 'OK file %s\n' "$1"
  else
    printf 'MISSING file %s\n' "$1" >&2
    fail=$((fail + 1))
  fi
}

# Bước 1: Kiểm tra công cụ PHP và Git
for tool in php git; do check_tool "$tool"; done

# Bước 2: Kiểm tra phiên bản PHP >= 8.1
if command -v php >/dev/null 2>&1; then
  if php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);'; then
    printf 'OK php_version %s\n' "$(php -r 'echo PHP_VERSION;')"
  else
    printf 'MISSING php_version_8_1_or_higher\n' >&2
    fail=$((fail + 1))
  fi
fi

# Bước 3: Kiểm tra cú pháp tệp PHP chính
if command -v php >/dev/null 2>&1 && [[ -f "$root/public/index.php" ]]; then
  if php -l "$root/public/index.php" >/dev/null 2>&1; then
    printf 'OK syntax public/index.php\n'
  else
    printf 'MISSING syntax public/index.php\n' >&2
    fail=$((fail + 1))
  fi
fi

# Bước 4: Kiểm tra sự tồn tại của các tệp dự án
for file in README.md public/index.php LICENSE; do
  check_file "$file"
done

# Trả mã kết quả
if [[ $fail -eq 0 ]]; then
  printf 'CHECK_ENV=PASS\n'
  exit 0
else
  printf 'CHECK_ENV=FAIL MISSING=%d\n' "$fail" >&2
  exit 1
fi
