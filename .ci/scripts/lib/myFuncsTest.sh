#!/bin/bash

echo -e "Starting Test..."

. .ci/scripts/lib/myFuncs

#
TEST_NAME_1="relse/alpha"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_1)
echo "$TEST_NAME_1 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_1="release/alpha"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_1)
echo "$TEST_NAME_1 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="release/alpha.BOB-joe.c"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="release/1.x"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="release/1.1.x"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="release/g-*&%%##"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="release/alpha/1.x"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"


#
TEST_NAME_2="release/a/1.x"
RETURNED_BRANCH_NAME=$(relBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"


#
TEST_NAME_2="persist/test-content"
RETURNED_BRANCH_NAME=$(persistBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="persist/alpha-all-new-content"
RETURNED_BRANCH_NAME=$(persistBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="persist/alpha_all_new_content"
RETURNED_BRANCH_NAME=$(persistBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="persist/alpha.BOB-joe.c"
RETURNED_BRANCH_NAME=$(persistBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"

#
TEST_NAME_2="persist/g-*&%%##"
RETURNED_BRANCH_NAME=$(persistBranchName $TEST_NAME_2)
echo "$TEST_NAME_2 :: $RETURNED_BRANCH_NAME"
