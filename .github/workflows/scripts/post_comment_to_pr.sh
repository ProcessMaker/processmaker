#!/bin/bash

# Abort if any error occurs
set -e

MESSAGE=$1

GITHUB_TOKEN=${GITHUB_TOKEN}
GITHUB_REPOSITORY=${GITHUB_REPOSITORY}
PR_NUMBER=${GITHUB_EVENT_PULL_REQUEST_NUMBER}

# Check there is a PR number available
if [ -z "$PR_NUMBER" ]; then
    echo "The PR number is not available. Make sure this script is executed in a context of Pull Request."
    exit 1
fi

URL="https://api.github.com/repos/${GITHUB_REPOSITORY}/issues/${PR_NUMBER}/comments"
json_payload=$(jq -n --arg message "$MESSAGE" '{"body": $message}')

# Send the message {body: MESSAGE} in json encoded
curl -s \
     -H "Authorization: token ${GITHUB_TOKEN}" \
     -H "Accept: application/vnd.github.v3+json" \
     -d "$json_payload" \
     "${URL}"