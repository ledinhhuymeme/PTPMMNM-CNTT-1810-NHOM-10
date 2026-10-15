#!/usr/bin/env bash
# Kiểm tra scripts/scan-source.sh với 4 trường hợp kiểm tra.
set -uo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." || exit 2
tmp="$(mktemp -d)" || exit 2
trap 'rm -rf -- "$tmp"' EXIT

# Tạo thư mục và tệp kiểm tra
mkdir -p "$tmp/valid/sub folder" "$tmp/mixed/sub folder" "$tmp/empty"
cp fixtures/valid/a.php "$tmp/valid/a.php"
cp 'fixtures/valid/sub folder/b.php' "$tmp/valid/sub folder/b.php"
cp -r "$tmp/valid/." "$tmp/mixed/"
cp fixtures/broken.txt "$tmp/mixed/broken.php"

failed=0

run_case() {
  local label="$1" dir="$2" expected="$3" summary="$4" actual output
  output="$(bash scripts/scan-source.sh "$dir" 2>&1)"; actual=$?

  if [[ $actual -eq $expected ]]; then
    printf 'PASS %s — %s\n' "$label" "$summary"
  else
    printf 'FAIL %s — %s (exit=%d expected=%d)\n' "$label" "$summary" "$actual" "$expected" >&2
    printf '  output: %s\n' "$output" >&2
    failed=$((failed + 1))
  fi
}

# Trường hợp 1: thư mục chỉ có tệp hợp lệ -> exit 0
run_case "valid" "$tmp/valid" 0 "all PHP files pass syntax check"

# Trường hợp 2: thư mục có tệp lỗi cú pháp -> exit 1
run_case "mixed" "$tmp/mixed" 1 "some PHP files fail syntax check"

# Trường hợp 3: thư mục rỗng (không có tệp PHP) -> exit 2
run_case "empty" "$tmp/empty" 2 "empty directory with no PHP files"

# Trường hợp 4: thư mục không tồn tại -> exit 2
run_case "missing-dir" "$tmp/does_not_exist" 2 "non-existent directory"

printf 'SHELL_CASES=4 FAILED=%d\n' "$failed"
[[ $failed -eq 0 ]]
