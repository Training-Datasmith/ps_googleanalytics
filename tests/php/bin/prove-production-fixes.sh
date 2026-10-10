#!/usr/bin/env bash
set -euo pipefail

if [[ "${PROVE_PRODUCTION_FIXES:-0}" != "1" ]]; then
  echo "Set PROVE_PRODUCTION_FIXES=1 to run mutation proofs." >&2
  exit 0
fi

ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
CANCEL_FILE="${ROOT}/classes/Hook/HookActionProductCancel.php"
WRAPPER_FILE="${ROOT}/classes/Wrapper/ProductWrapper.php"
PHAR_PATH="${PHAR_PATH:-/tmp/ps-googleanalytics-phpunit/phpunit-7.5.20.phar}"
EXPECTED_SHA256="472b6e275e4479bab71f2f7744d2e22ace9566a4e3cef37952e4d9c07f6d3a92"

if ! git -C "${ROOT}" diff --quiet || ! git -C "${ROOT}" diff --cached --quiet; then
  echo "Refusing to run mutation proofs on a dirty working tree." >&2
  exit 1
fi

if [[ ! -f "${PHAR_PATH}" ]]; then
  echo "Verified PHPUnit PHAR not found at ${PHAR_PATH}; run tests/run-suite.sh first." >&2
  exit 1
fi
echo "${EXPECTED_SHA256}  ${PHAR_PATH}" | sha256sum -c -

BACKUP_DIR="$(mktemp -d)"
trap 'cp -a "${BACKUP_DIR}/HookActionProductCancel.php" "${CANCEL_FILE}"; cp -a "${BACKUP_DIR}/ProductWrapper.php" "${WRAPPER_FILE}"; rm -rf "${BACKUP_DIR}"' EXIT INT TERM
cp -a "${CANCEL_FILE}" "${BACKUP_DIR}/HookActionProductCancel.php"
cp -a "${WRAPPER_FILE}" "${BACKUP_DIR}/ProductWrapper.php"

DOCKER=(sudo docker compose -f "${ROOT}/tests/docker-compose.yml" run --rm -T
  -v "${PHAR_PATH}:/opt/phpunit/phpunit-7.5.20.phar:ro" php
  php -d auto_prepend_file=/src/tests/php/ini/prepend.php -d error_reporting=-1
  /opt/phpunit/phpunit-7.5.20.phar --configuration tests/phpunit.xml)

expect_failures_for_filter() {
  local filter="$1"
  local log
  log="$(mktemp)"
  set +e
  "${DOCKER[@]}" --filter "${filter}" >"${log}" 2>&1
  set -e
  if ! grep -q '^FAILURES!' "${log}"; then
    echo "Expected FAILURES! for filter ${filter}" >&2
    cat "${log}" >&2
    rm -f "${log}"
    exit 1
  fi
  if grep -qE 'Errors: [1-9]' "${log}"; then
    echo "Expected failures only (no errors) for filter ${filter}" >&2
    cat "${log}" >&2
    rm -f "${log}"
    exit 1
  fi
  if ! grep -qE 'Failures: [1-9]' "${log}"; then
    echo "Expected at least one failure for filter ${filter}" >&2
    cat "${log}" >&2
    rm -f "${log}"
    exit 1
  fi
  rm -f "${log}"
}

# Revert fix (a): cast item_id to int
sed -i "s/'item_id' => \$idProduct/'item_id' => (int) \$idProduct/" "${CANCEL_FILE}"
expect_failures_for_filter 'HookActionProductCancelTest'
cp -a "${BACKUP_DIR}/HookActionProductCancel.php" "${CANCEL_FILE}"

# Revert fix (b) to empty(): breaks numeric zero preservation
python3 << 'PY'
from pathlib import Path
p = Path('classes/Wrapper/ProductWrapper.php')
text = p.read_text()
old = """        if (empty($product['price_amount'])
            && !(isset($product['price_amount']) && is_numeric($product['price_amount']))
        ) {
            $product['price_amount'] = $product['price'];
        }"""
new = """        if (empty($product['price_amount'])) {
            $product['price_amount'] = $product['price'];
        }"""
if old not in text:
    raise SystemExit('could not revert price_amount fix to empty() for proof')
p.write_text(text.replace(old, new))
PY
expect_failures_for_filter 'ProductWrapperTest::testPrepareItemKeepsNumericZeroPriceAmount'
expect_failures_for_filter 'ProductWrapperTest::testStringZeroPriceAmountIsPreserved'
cp -a "${BACKUP_DIR}/ProductWrapper.php" "${WRAPPER_FILE}"

# Drop is_numeric guard: [] no longer falls back
python3 << 'PY'
from pathlib import Path
p = Path('classes/Wrapper/ProductWrapper.php')
text = p.read_text()
old = """        if (empty($product['price_amount'])
            && !(isset($product['price_amount']) && is_numeric($product['price_amount']))
        ) {
            $product['price_amount'] = $product['price'];
        }"""
new = """        if (empty($product['price_amount'])
            && !(isset($product['price_amount']))
        ) {
            $product['price_amount'] = $product['price'];
        }"""
if old not in text:
    raise SystemExit('could not drop is_numeric guard for proof')
p.write_text(text.replace(old, new))
PY
expect_failures_for_filter 'ProductWrapperTest::testPrepareItemFallsBackWhenPriceAmountMissingOrEmpty'

echo "Mutation proofs passed: tests fail when fixes are reverted."
