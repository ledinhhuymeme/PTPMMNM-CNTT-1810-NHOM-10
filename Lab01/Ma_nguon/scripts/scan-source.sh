#!/usr/bin/env bash
# Script quét tệp PHP và kiểm tra cú pháp.
# Bài tự mở rộng: sinh viên điền các TODO để hoàn thiện ba vị trí.
set -uo pipefail

# Kiểm tra tham số đầu vào
if [[ $# -ne 1 ]]; then echo 'Usage: scan-source.sh DIRECTORY' >&2; exit 2; fi
root="$1"

# TODO 1: Kiểm tra thư mục tồn tại, có thể đọc được và PHP có sẵn; nếu không -> exit 2.
if [[ ! -d "$root" ]]; then
  echo "MISSING_DIR: $root" >&2
  exit 2
fi
if [[ ! -r "$root" ]]; then
  echo "UNREADABLE_DIR: $root" >&2
  exit 2
fi
if ! command -v php >/dev/null 2>&1; then
  echo 'THIEU_PHP' >&2
  exit 2
fi

# Tạo tệp tạm để lưu danh sách tệp PHP (null-terminated để xử lý tên có khoảng trắng)
list="$(mktemp)" || exit 2
trap 'rm -f -- "$list"' EXIT

# Tìm tất cả tệp .php trong thư mục và thư mục con (bao gồm tên có khoảng trắng)
if ! find "$root" -type f -name '*.php' -print0 >"$list"; then exit 2; fi

total=0; passed=0; failed=0

# TODO 2: Duyệt từng tệp, kiểm tra cú pháp PHP, tăng bộ đếm phù hợp.
while IFS= read -r -d '' file; do
  total=$((total + 1))
  if php -l "$file" >/dev/null 2>&1; then
    printf 'OK %s\n' "$file"
    passed=$((passed + 1))
  else
    printf 'FAIL %s\n' "$file" >&2
    failed=$((failed + 1))
  fi
done <"$list"

# In tổng kết
printf 'TOTAL=%d PASSED=%d FAILED=%d\n' "$total" "$passed" "$failed"

# TODO 3: Trả mã thoát đúng:
#   - exit 2 nếu thư mục rỗng (total = 0) hoặc thiếu điều kiện
#   - exit 1 nếu có tệp sai cú pháp (failed > 0)
#   - exit 0 nếu tất cả đều đúng
if [[ $total -eq 0 ]]; then
  exit 2
elif [[ $failed -gt 0 ]]; then
  exit 1
else
  exit 0
fi
