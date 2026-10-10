#!/usr/bin/env bash
set -euo pipefail

if [[ "${PROVE_PRODUCTION_FIXES:-0}" != "1" ]]; then
  echo "Set PROVE_PRODUCTION_FIXES=1 to run mutation proofs." >&2
  exit 0
fi

ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
TMP_BRANCH="prove-mutations-$(date +%s)"
trap 'cd "$ROOT"; git checkout remote-test-2026-10-09 >/dev/null 2>&1 || true; git branch -D "$TMP_BRANCH" >/dev/null 2>&1 || true' EXIT INT TERM

cd "$ROOT"
git checkout -b "$TMP_BRANCH"

# Revert fix (a)
git checkout HEAD~0 -- classes/Hook/HookActionProductCancel.php
sed -i "s/'item_id' => \$idProduct/'item_id' => (int) \$idProduct/" classes/Hook/HookActionProductCancel.php

PHAR_PATH="${PHAR_PATH:-/tmp/ps-googleanalytics-phpunit/phpunit-7.5.20.phar}"
DOCKER=(sudo docker compose -f tests/docker-compose.yml run --rm
  -v "${PHAR_PATH}:/opt/phpunit/phpunit-7.5.20.phar:ro" php
  php -d auto_prepend_file=/src/tests/php/ini/prepend.php -d error_reporting=-1
  /opt/phpunit/phpunit-7.5.20.phar --configuration tests/phpunit.xml)

if "${DOCKER[@]}" --filter HookActionProductCancelTest; then
  echo "Expected HookActionProductCancelTest to fail without fix (a)" >&2
  exit 1
fi

git checkout remote-test-2026-10-09 -- classes/Hook/HookActionProductCancel.php

# Revert fix (b)
sed -i 's/if (!isset($product\[\x27price_amount\x27\])/,if (empty($product[\x27price_amount\x27])) { $product[\x27price_amount\x27]=$product[\x27price\x27]; } if (false/' classes/Wrapper/ProductWrapper.php || true

python3 << 'PY'
from pathlib import Path
p = Path('classes/Wrapper/ProductWrapper.php')
text = p.read_text()
old = """        if (!isset($product['price_amount'])
            || $product['price_amount'] === ''
            || $product['price_amount'] === null
            || $product['price_amount'] === false
        ) {
            $product['price_amount'] = $product['price'];
        }"""
new = """        if (empty($product['price_amount'])) {
            $product['price_amount'] = $product['price'];
        }"""
if old not in text:
    raise SystemExit('could not revert price_amount fix for proof')
p.write_text(text.replace(old, new))
PY

if "${DOCKER[@]}" --filter ProductWrapperTest; then
  echo "Expected ProductWrapperTest to fail without fix (b)" >&2
  exit 1
fi

echo "Mutation proofs passed: tests fail when fixes are reverted."
