
TARGET_DIR=build
OUTPUT_FILE="phpDbManager.php"

.PHONY: all


all: main
	rm $(TMP_FILE)

prepare:
	TMP_FILE=mktemp _buildXXXX.php
	echo $(TMP_FILE)

main: prepare

