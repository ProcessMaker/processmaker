#!/bin/bash

# TestSuites to execute: TCP4-2065, TCP4-2072
# Set the test suite ID here
testSuites=(245814 245815)

# Timeout in minutes, Default 1 hour.
maxTimeOut=60

declare -A testsStatus
declare -A testsHashes
declare -A testsRequest

# Default Values
# TODO: Move AppID and AppCode to EnvVars
AppID="15812058"
AppCode="47810485"

echo "Sending tests to EndTest platform"
for test in "${testSuites[@]}"
do
    testsStatus[$test]=""
    testsHashes[$test]=""
    testsRequest[$test]="https://endtest.io/api.php?action=runTestSuite&appId=${AppID}&appCode=${AppCode}&testSuite=${test}&selectedPlatform=windows&selectedOs=a&selectedBrowser=chrome&selectedResolution=i&selectedLocation=sanfrancisco&selectedCases=all&writtenAdditionalNotes=TestFromAPI"

    echo "Get Hash for execution test suite id: $test"
    testsHashes[$test]=$(curl -s -X GET --header "Accept: */*" "${testsRequest[$test]}")
    echo "Hash: ${testsHashes[$test]}"
done

pendingTests=true
exitCode=0
timeElapsed=0
echo "Tests Started, Retrieving Test Suite info..."
while $pendingTests; do
    pendingTests=false
    if [[ $timeElapsed -le $maxTimeOut ]]; then
        timeElapsed=$((timeElapsed + 1))
        echo "Tests in progress..."
        sleep 60
        for test in "${testSuites[@]}"
        do
            if [[ ${testsStatus[$test]} == "Completed" || ${testsStatus[$test]} == "Erred." ]]; then
                continue
            fi

            result=$(curl -s -X GET --header "Accept: */*" "https://endtest.io/api.php?action=getResults&appId=${AppID}&appCode=${AppCode}&hash=${testsHashes[$test]}&format=json")
            if [ "$result" == "Test is still running." ]
            then
                testsStatus[$test]=$result
                pendingTests=true
            elif [ "$result" == "Processing video recording." ]
            then
                testsStatus[$test]=$result
                pendingTests=true
            elif [ "$result" == "Stopping." ]
            then
                testsStatus[$test]=$result
                pendingTests=true
            elif [ "$result" == "Erred." ]
            then
                testsStatus[$test]=$result
                echo "TestSuite failed for ID: $test, Status: $result"
                echo "Please check this link for detailed info: https://endtest.io/results?hash=${testsHashes[$test]}"
                exitCode=1
            elif [ "$result" == "" ]
            then
                testsStatus[$test]=$result
            else
                # echo "$result" | jq
                # TODO: Upload results as artifacts on CircleCI
                echo "$result" > "$test".json
                testsStatus[$test]="Completed"
            fi
        done
    else
        echo "Timeout of $maxTimeOut minutes exceeded."
        exit $exitCode
    fi
done

if [ $exitCode -ne 0 ]; then
    echo "TestSuites finished with Errors, please see logs above."
else
    echo "TestSuites finished successfully."
fi

exit $exitCode
