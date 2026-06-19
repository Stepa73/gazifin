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

PLATFORM="${DOCKER_PLATFORM:-linux/amd64}"

echo "Building ${IMAGE} for ${PLATFORM}..."
docker build \
    --platform "${PLATFORM}" \
    -f "${ROOT_DIR}/docker/Dockerfile" \
    -t "${IMAGE}" \
    "${ROOT_DIR}"

if command -v git >/dev/null 2>&1 && git -C "${ROOT_DIR}" rev-parse --short HEAD >/dev/null 2>&1; then
    GIT_TAG="${DOCKER_IMAGE}:${DOCKER_TAG}-$(git -C "${ROOT_DIR}" rev-parse --short HEAD)"
    docker tag "${IMAGE}" "${GIT_TAG}"
    echo "Tagged also as ${GIT_TAG}"
fi

echo "Build dokončen: ${IMAGE}"
