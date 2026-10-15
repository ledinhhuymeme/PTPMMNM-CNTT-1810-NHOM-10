#!/usr/bin/env bash
# Kiểm tra scripts/check-env.sh đã đáp ứng đúng phiên bản môi trường kiểm tra.
set -uo pipefail
cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." || exit 2

failed=0
pass_count=0

run_case() {
  local label="$1" expected_exit="$2" expected_output="$3"
  local actual_output actual_exit

  actual_output="$(bash scripts/check-env.sh 2>&1)"
  actual_exit=$?

  if [[ $actual_exit -eq $expected_exit ]] && echo "$actual_output" | grep -qF "$expected_output"; then
    printf 'PASS %s\n' "$label"
    pass_count=$((pass_count + 1))
  else
    printf 'FAIL %s (exit=%d expected=%d)\n' "$label" "$actual_exit" "$expected_exit" >&2
    printf '  output: %s\n' "$actual_output" >&2
    failed=$((failed + 1))
  fi
}

# Kiểm tra script chạy thành công với môi trường hiện tại
run_case "check-env runs" 0 "CHECK_ENV=PASS"

printf 'SHELL_CASES=%d FAILED=%d\n' "$((pass_count + failed))" "$failed"
[[ $failed -eq 0 ]]
