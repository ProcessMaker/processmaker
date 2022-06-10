set -ex
export PARALLEL_TEST_PROCESSES=6
# export LOG_CHANNEL=stack
export SAVED_SEARCH_COUNT=false

vendor/bin/paratest -p 6 "$@"
