#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ENV_FILE="${ROOT_DIR}/docker/.env.build"

if [[ ! -f "${ENV_FILE}" ]]; then
    echo "Chybí ${ENV_FILE}. Zkopírujte docker/.env.build.example."
    exit 1
fi

# shellcheck disable=SC1090
source "${ENV_FILE}"

IMAGE="${DOCKER_IMAGE}:${DOCKER_TAG}"

if ! docker info >/dev/null 2>&1; then
    echo "Docker není dostupný."
    exit 1
fi

echo "Pushing ${IMAGE}..."
docker push "${IMAGE}"

echo "Push dokončen."
