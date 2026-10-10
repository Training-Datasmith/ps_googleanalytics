#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHAR_DIR="/tmp/ps-googleanalytics-phpunit"
PHAR_PATH="${PHAR_DIR}/phpunit-7.5.20.phar"
PHAR_ASC="${PHAR_DIR}/phpunit-7.5.20.phar.asc"
EXPECTED_SHA256="472b6e275e4479bab71f2f7744d2e22ace9566a4e3cef37952e4d9c07f6d3a92"
EXPECTED_FPR="D8406D0D82947747293778314AA394086372C20A"
ARTIFACT="/opt/cursor/artifacts/suite-results.md"
EXPECTED_TOTAL=29
EXPECTED_METHODS=49
mapfile -t EXPECTED_FILES <<'EOF'
Controller/AdminAjaxControllerTest.php
Controller/FrontAjaxControllerTest.php
Database/InstallUninstallTest.php
Form/ConfigurationFormTreatTest.php
GoogleAnalyticsToolsTest.php
Guard/IndexPhpGuardTest.php
Guard/PsVersionGuardTest.php
Handler/GanalyticsDataHandlerTest.php
Handler/GanalyticsJsHandlerTest.php
Handler/ModuleHandlerTest.php
Hook/HookActionCarrierProcessTest.php
Hook/HookActionCartUpdateQuantityBeforeTest.php
Hook/HookActionObjectProductInCartDeleteBeforeTest.php
Hook/HookActionOrderStatusPostUpdateTest.php
Hook/HookActionProductCancelTest.php
Hook/HookActionValidateOrderTest.php
Hook/HookDisplayBackOfficeHeaderTest.php
Hook/HookDisplayBeforeBodyClosingTagTest.php
Hook/HookDisplayFooterProductTest.php
Hook/HookDisplayHeaderTest.php
Hook/HookDisplayOrderConfirmationTest.php
Hook/HookInterfaceContractTest.php
Module/PsGoogleanalyticsTest.php
Repository/CarrierRepositoryTest.php
Repository/GanalyticsDataRepositoryTest.php
Repository/GanalyticsRepositoryTest.php
Upgrade/UpgradeScriptsTest.php
Wrapper/OrderWrapperTest.php
Wrapper/ProductWrapperTest.php
EOF

mkdir -p "${PHAR_DIR}" "$(dirname "${ARTIFACT}")"

GNUPGHOME="$(mktemp -d)"
chmod 0700 "${GNUPGHOME}"
cleanup_gpg() { rm -rf "${GNUPGHOME}"; }
trap cleanup_gpg EXIT INT TERM

curl -fsSL -o "${PHAR_PATH}" https://phar.phpunit.de/phpunit-7.5.20.phar
curl -fsSL -o "${PHAR_ASC}" https://phar.phpunit.de/phpunit-7.5.20.phar.asc
curl -fsSL -o "${PHAR_DIR}/sebastian.asc" https://sebastian-bergmann.de/gpg.asc
echo "${EXPECTED_SHA256}  ${PHAR_PATH}" | sha256sum -c -
GNUPGHOME="${GNUPGHOME}" gpg --batch --import "${PHAR_DIR}/sebastian.asc"
IMPORTED_FPR="$(GNUPGHOME="${GNUPGHOME}" gpg --batch --with-colons --fingerprint "Sebastian Bergmann" | awk -F: '/^fpr:/{print $10; exit}')"
test "${IMPORTED_FPR}" = "${EXPECTED_FPR}"
GNUPGHOME="${GNUPGHOME}" gpg --batch --status-fd 1 --verify "${PHAR_ASC}" "${PHAR_PATH}" \
  | awk '/^\[GNUPG:\] VALIDSIG /{print $3; found=1} END{exit !found}' \
  | grep -qx "${EXPECTED_FPR}"

ACTUAL_FILES="$(find "${REPO_ROOT}/tests/php" -name '*Test.php' -printf '%P\n' | LC_ALL=C sort)"
EXPECTED_SORTED="$(printf '%s\n' "${EXPECTED_FILES[@]}" | LC_ALL=C sort)"
if [[ "$(printf '%s\n' "${EXPECTED_SORTED}" | wc -l)" -ne "${EXPECTED_TOTAL}" ]]; then
  echo "EXPECTED_FILES count mismatch" >&2
  exit 1
fi
if [[ "${ACTUAL_FILES}" != "${EXPECTED_SORTED}" ]]; then
  echo "Test file inventory mismatch" >&2
  printf 'expected:\n%s\nactual:\n%s\n' "${EXPECTED_SORTED}" "${ACTUAL_FILES}" >&2
  exit 1
fi

ACTUAL_METHODS="$(find "${REPO_ROOT}/tests/php" -name '*Test.php' -exec grep -c 'function test' {} + | awk -F: '{s+=$2} END{print s}')"
if [[ "${ACTUAL_METHODS}" != "${EXPECTED_METHODS}" ]]; then
  echo "Test method count mismatch (expected ${EXPECTED_METHODS}, got ${ACTUAL_METHODS})" >&2
  exit 1
fi

if git -C "${REPO_ROOT}" diff --name-only | grep -qE '^(composer\.json|composer\.lock|configuration/test-baselines\.tsv)$'; then
  echo "Forbidden files modified" >&2
  exit 1
fi

cd "${REPO_ROOT}"
DOCKER=(sudo docker compose -f tests/docker-compose.yml)
"${DOCKER[@]}" build php
"${DOCKER[@]}" up -d mariadb
i=0
until [[ "$(sudo docker compose -f tests/docker-compose.yml ps mariadb --format '{{.Health}}' 2>/dev/null || echo starting)" == "healthy" ]]; do
  i=$((i + 1))
  if [[ "${i}" -ge 60 ]]; then
    echo "MariaDB did not become healthy" >&2
    exit 1
  fi
  sleep 1
done

RUN_PHP=(sudo docker compose -f tests/docker-compose.yml run --rm -T
  -v "${PHAR_PATH}:/opt/phpunit/phpunit-7.5.20.phar:ro" php
  php -d auto_prepend_file=/src/tests/php/ini/prepend.php -d error_reporting=-1 -d display_errors=1
  /opt/phpunit/phpunit-7.5.20.phar --configuration tests/phpunit.xml)

LOG_DIR="$(mktemp -d)"
assert_run_ok() {
  local label="$1"
  local logfile="${LOG_DIR}/${label}.txt"
  local summary
  summary="$(grep -E '^OK \(|^FAILURES!|^Tests:' "${logfile}" | tail -1)"
  if [[ ! "${summary}" =~ ^OK\ \(([0-9]+)\ tests,\ ([0-9]+)\ assertions\) ]]; then
    echo "Run ${label} did not end with OK (... tests, ... assertions): ${summary}" >&2
    exit 1
  fi
  if [[ "${BASH_REMATCH[1]}" -lt "${EXPECTED_METHODS}" ]]; then
    echo "Run ${label} ran ${BASH_REMATCH[1]} tests, expected at least ${EXPECTED_METHODS}" >&2
    exit 1
  fi
}

run_once() {
  local label="$1"
  shift
  "${RUN_PHP[@]}" "$@" | tee "${LOG_DIR}/${label}.txt"
  assert_run_ok "${label}"
}

run_once default-1
run_once default-2
run_once random-1 --order-by=random --random-order-seed=20261008
run_once random-2 --order-by=random --random-order-seed=20261008

SHA="$(git -C "${REPO_ROOT}" rev-parse HEAD)"
BRANCH="$(git -C "${REPO_ROOT}" rev-parse --abbrev-ref HEAD)"
PHP_VER="$(sudo docker compose -f tests/docker-compose.yml run --rm -T php php -r 'echo PHP_VERSION;')"

LAST_LOG="${LOG_DIR}/random-2.txt"
SUMMARY_LINE="$(grep -E '^OK \(' "${LAST_LOG}" | tail -1)"
TESTS=""
ASSERTIONS=""
FAILURES=0
ERRORS=0
SKIPPED=0
if [[ "${SUMMARY_LINE}" =~ OK\ \(([0-9]+)\ tests,\ ([0-9]+)\ assertions\) ]]; then
  TESTS="${BASH_REMATCH[1]}"
  ASSERTIONS="${BASH_REMATCH[2]}"
fi

{
  echo "# ps_googleanalytics suite results"
  echo "- repo: Training-Datasmith/ps_googleanalytics"
  echo "- date: $(date -u +%Y-%m-%dT%H:%M:%SZ)"
  echo "- branch: ${BRANCH}"
  echo "- sha: ${SHA}"
  echo "- test files: ${EXPECTED_TOTAL}"
  echo "- test methods: ${EXPECTED_METHODS}"
  echo "- tests: ${TESTS}"
  echo "- assertions: ${ASSERTIONS:-unknown}"
  echo "- failures: ${FAILURES:-0}"
  echo "- errors: ${ERRORS:-0}"
  echo "- skipped: ${SKIPPED:-0}"
  echo "- php: ${PHP_VER}"
  echo "- command: tests/run-suite.sh (4 runs: default x2, random seed 20261008 x2)"
  echo "- notes: PHPUnit 7.5.20 PHAR verified SHA256 and VALIDSIG ${EXPECTED_FPR}"
} > "${ARTIFACT}"

sudo docker compose -f tests/docker-compose.yml down

echo "Results written to ${ARTIFACT}"
